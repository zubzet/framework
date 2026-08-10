<?php

    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Database\Endpoint;

    class ConnectionProbeController extends z_controller {

        // -------------------------------------------------------------
        // switchUser
        // -------------------------------------------------------------

        public function action_switchUser(Request $req, Response $res) {
            $db = db();
            $original = ["user" => config("dbusername"), "password" => config("dbpassword")];
            $before = $db->exec("SELECT CURRENT_USER() AS u")->resultToLine()["u"];

            try {
                $db->switchUser("root", "root_password");
                $during = $db->exec("SELECT CURRENT_USER() AS u")->resultToLine()["u"];
            } finally {
                $db->switchUser($original["user"], $original["password"]);
            }

            $after = $db->exec("SELECT CURRENT_USER() AS u")->resultToLine()["u"];
            return $res->json(compact("before", "during", "after"));
        }

        // -------------------------------------------------------------
        // exec() failure paths. The bind-fail and STMT-errno checks
        // were removed as unreachable on PHP 8; what's left is the
        // prepare-fail and execute-fail branches.
        // -------------------------------------------------------------

        public function action_execPrepareFail(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => db()->exec("SELECT FROM WHERE"));
        }

        public function action_execBindFail(Request $req, Response $res) {
            // Argument count mismatch: types="is" expects 2 bound values
            // but only 1 is supplied. PHP 8 throws ArgumentCountError
            // before bind_param could return false.
            return $this->catchThrowableMessage(fn() =>
                db()->exec("SELECT * FROM z_user WHERE id=? AND email=?", "is", 1)
            );
        }

        public function action_execExecuteFail(Request $req, Response $res) {
            // NULL into a NOT NULL column - execute() returns false and
            // the framework wraps it in its "SQL Execution Error" Exception.
            return $this->catchThrowableMessage(fn() =>
                db()->exec(
                    "INSERT INTO z_test_grouping (group_id, label, val) VALUES (?, ?, ?)",
                    "isi", null, "X", 1,
                )
            );
        }

        // -------------------------------------------------------------
        // heartbeat()
        // -------------------------------------------------------------

        public function action_heartbeatForce(Request $req, Response $res) {
            $this->ensureConnection();
            return $res->json(["alive" => db()->heartbeat(false)]);
        }

        public function action_heartbeatRecent(Request $req, Response $res) {
            $db = db();
            $this->ensureConnection();
            unset($db->lastHeartbeat);
            $first = $db->heartbeat(true);
            $lastH1 = $db->lastHeartbeat;
            sleep(1);
            $second = $db->heartbeat(true);
            $lastH2 = $db->lastHeartbeat;
            return $res->json([
                "first" => $first,
                "second" => $second,
                "lastHeartbeatStable" => $lastH1 === $lastH2,
            ]);
        }

        // Regression guard for the lazy-loading change: a freshly constructed
        // Connection opens no socket, so its typed \mysqli $conn property is
        // uninitialized. Calling heartbeat() on it used to fatal with "Typed
        // property ...::$conn must not be accessed before initialization" once
        // the connection stopped being opened eagerly in the constructor.
        // It must now report not-alive instead of throwing, so existing worker
        // keep-alive loops keep working after upgrading. A fresh instance is
        // used (rather than db()) so the uninitialized state is guaranteed
        // regardless of what else the request touched.
        public function action_heartbeatBeforeConnect(Request $req, Response $res) {
            $threw = false;
            $alive = null;
            try {
                $alive = (new Connection())->heartbeat(false);
            } catch (\Throwable $e) {
                $threw = true;
            }
            return $res->json(["threw" => $threw, "alive" => $alive]);
        }

        // -------------------------------------------------------------
        // assertConnection() - happy + stale-then-heartbeat. The
        // heartbeat-fails-then-reconnect branch is intentionally out of
        // scope (would need MySQL fault injection).
        // -------------------------------------------------------------

        public function action_assertConnectionHappy(Request $req, Response $res) {
            // First exec primes lastConnect; the second exec hits the
            // "recently connected, no work needed" branch.
            $db = db();
            $db->exec("SELECT 1");
            $row = $db->exec("SELECT 1 AS v")->resultToLine();
            return $res->json(["value" => (int) $row["v"]]);
        }

        public function action_assertConnectionViaHeartbeat(Request $req, Response $res) {
            $db = db();
            $this->ensureConnection();
            $db->lastConnect = 1; // forces past connectTimeout
            unset($db->lastHeartbeat);
            $row = $db->exec("SELECT 1 AS v")->resultToLine();
            return $res->json([
                "value" => (int) $row["v"],
                "heartbeatBumped" => isset($db->lastHeartbeat),
            ]);
        }

        // -------------------------------------------------------------
        // execQuery() - runs a Cake\Database\Query through the framework's
        // own value-binder. Drives both branches: empty bindings (pass
        // through to exec($sql) directly) and typed bindings.
        // -------------------------------------------------------------

        public function action_execQueryWithBindings(Request $req, Response $res) {
            $query = db()->queryBuilderConnection
                ->selectQuery(["group_id", "label", "val"], "z_test_grouping")
                ->where(["group_id" => 1]);
            $rows = db()->execQuery($query)->resultToArray();
            return $res->json($rows);
        }

        public function action_execQueryWithoutBindings(Request $req, Response $res) {
            // No WHERE -> Cake emits the SQL with zero parameter bindings,
            // which exercises the early-return branch in execQuery().
            $query = db()->queryBuilderConnection
                ->selectQuery(["group_id", "label", "val"], "z_test_grouping");
            $rows = db()->execQuery($query)->resultToArray();
            return $res->json($rows);
        }

        public function action_execQueryAllBindingTypes(Request $req, Response $res) {
            // Cake's binder reports column types as integer/float/string;
            // execQuery maps each to 'i'/'d'/'s'. The where() callback
            // pins each type explicitly so all three switch arms run.
            $query = db()->queryBuilderConnection
                ->selectQuery(["group_id", "label", "val"], "z_test_grouping")
                ->where(function($exp) {
                    return $exp
                        ->eq("group_id", 1, "integer")
                        ->eq("val", 10.0, "float")
                        ->eq("label", "A", "string");
                })
                ->limit(1);

            $rows = db()->execQuery($query)->resultToArray();
            return $res->json(["count" => count($rows)]);
        }

        // -------------------------------------------------------------
        // executeMultiQuery() - happy + non-throwing failure.
        // -------------------------------------------------------------

        public function action_executeMultiQueryHappy(Request $req, Response $res) {
            $ok = db()->executeMultiQuery("SELECT 1; SELECT 2; SELECT 3;");
            return $res->json(["ok" => $ok]);
        }

        public function action_executeMultiQueryFailSwallowed(Request $req, Response $res) {
            // $throwOnFailure=false makes the multi-query return false
            // on the first invalid statement instead of throwing.
            $ok = db()->executeMultiQuery(
                "SELECT 1; SELECT FROM WHERE;",
                throwOnFailure: false,
            );
            return $res->json(["ok" => $ok]);
        }

        public function action_executeMultiQueryThrows(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() =>
                db()->executeMultiQuery("SELECT 1; INVALID SYNTAX HERE;")
            );
        }

        // -------------------------------------------------------------
        // getDatabaseConnection() - returns the underlying mysqli handle.
        // -------------------------------------------------------------

        public function action_getDatabaseConnection(Request $req, Response $res) {
            $conn = db()->getDatabaseConnection();
            return $res->json([
                "isMysqli" => $conn instanceof \mysqli,
                "serverInfo" => $conn->server_info,
            ]);
        }

        // disconnect() is already covered transitively: every connect()
        // call (including the very first one and each switchUser()) runs
        // disconnect() first, hitting both the "not yet connected /
        // early-return" branch and the "close the open handle" branch
        // without a dedicated probe.

        // -------------------------------------------------------------
        // Constructor: non-numeric db_connection_timeout config throws
        // InvalidArgumentException. Build a fresh Connection instance
        // with the override in effect (zubzet()->db_connection_timeout
        // is read by config() during construction).
        // -------------------------------------------------------------

        public function action_constructorNonNumericTimeout(Request $req, Response $res) {
            zubzet()->db_connection_timeout = "not-a-number";
            return $this->catchThrowableMessage(fn() => new Connection());
        }

        // Exercises the no-throw branch of catchThrowableMessage so the
        // helper stays at 100% coverage even with all the deliberately-
        // throwing probes above. Pass a closure that runs cleanly.
        public function action_catchHelperHappyPath(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => /* no throw */ null);
        }

        // -------------------------------------------------------------
        // TLS (src/Database/Endpoint.php). The cluster's certificate comes
        // from the suite's own CA, which the application image trusts in
        // its system store (Dockerfile.apache-local) exactly like a public
        // authority in production, so db_ssl = true needs nothing else.
        // The settings are overridden per request so the application's own
        // connection stays usable in the cases that must fail to connect;
        // the ini-driven path is covered by the spec flipping db_ssl on a
        // cluster that demands it. The suite runs with db_persistent on,
        // so connectWith() forces it off: mysqli pools by credentials and
        // endpoint, not by transport, and a pooled plaintext connection
        // would be handed to the TLS cases.
        // -------------------------------------------------------------

        /** How the application's configured connection is talking right now. */
        public function action_tlsTransport(Request $req, Response $res) {
            return $res->json($this->transportOf(db()));
        }

        /** db_ssl = true alone: encrypted, verified via the system store. */
        public function action_tlsVerified(Request $req, Response $res) {
            return $this->connectWith(["db_ssl" => true], $res);
        }

        /**
         * The endpoint's IP is not a name on the certificate, so this must
         * be refused. Proof that verification really happens - mysqli given
         * no authority skips every check and would connect happily.
         */
        public function action_tlsWrongHost(Request $req, Response $res) {
            return $this->connectWith(["db_ssl" => true, "dbhost" => gethostbyname("database")], $res);
        }

        /** A host:port dbhost cannot be verified; the error must say why. */
        public function action_tlsHostWithPort(Request $req, Response $res) {
            return $this->connectWith(["db_ssl" => true, "dbhost" => "database:3306"], $res);
        }

        // -------------------------------------------------------------
        // db_persistent: mysqli keeps the connection open in this PHP
        // worker and hands it to the next request. Two connections opened
        // one after the other in a single request are the same server
        // session with it, and two different ones without it. Comparing
        // within one request keeps this independent of which apache worker
        // a request happens to land on.
        // -------------------------------------------------------------

        public function action_persistentReuse(Request $req, Response $res) {
            return $res->json([
                "persistent" => $this->twoSessions(true),
                "fresh" => $this->twoSessions(false),
            ]);
        }

        /** Identifies the server session of two consecutive connections. */
        private function twoSessions(bool $persistent): array {
            return $this->withSettings(["db_persistent" => $persistent], function() {
                $sessions = [];
                for($i = 0; $i < 2; $i++) {
                    $connection = new Connection();
                    // Node included because thread ids are per node, so two
                    // separate sessions can share a number across the cluster.
                    $session = $connection->exec("SELECT @@hostname AS node, CONNECTION_ID() AS id")->resultToLine();
                    $sessions[] = $session["node"] . "#" . $session["id"];
                    $connection->disconnect();
                }
                return $sessions;
            });
        }

        // -------------------------------------------------------------
        // helpers
        // -------------------------------------------------------------

        /**
         * Opens a connection under the given settings and reports how it
         * went. Failures are reported as data, not as a 500, so the spec can
         * assert on the negative cases too.
         */
        private function connectWith(array $settings, Response $res) {
            // Fresh, non-pooled connections; see the section comment.
            $settings += ["db_persistent" => false];
            try {
                return $res->json($this->withSettings($settings, function() {
                    // Constructed inside the override so it picks the settings
                    // up; the socket itself is opened by the first query.
                    $connection = new Connection();
                    $transport = $this->transportOf($connection);
                    $connection->disconnect();
                    return $transport + ["connected" => true, "error" => null];
                }));
            } catch(\Throwable $failure) {
                return $res->json(["connected" => false, "error" => $failure->getMessage()]);
            }
        }

        /** Runs the callback with booter settings temporarily overridden. */
        private function withSettings(array $settings, \Closure $action) {
            $restore = [];
            foreach($settings as $key => $value) {
                $restore[$key] = isset(zubzet()->{$key}) ? zubzet()->{$key} : null;
                zubzet()->{$key} = $value;
            }

            self::resetEndpoint();
            try {
                return $action();
            } finally {
                foreach($restore as $key => $value) {
                    zubzet()->{$key} = $value;
                }
                self::resetEndpoint();
            }
        }

        /**
         * The framework reads the endpoint once per request (a singleton);
         * these probes vary it within one request, so the cached instance is
         * cleared around every override. Test-only reflection, deliberately
         * not a framework API.
         *
         * setStaticPropertyValue() reaches the private property on every
         * supported version: ReflectionProperty would need setAccessible()
         * on 8.0, which is deprecated as of 8.5.
         */
        private static function resetEndpoint(): void {
            (new \ReflectionClass(Endpoint::class))->setStaticPropertyValue("instance", null);
        }

        /**
         * The transport actually negotiated for this session. Both status
         * variables are empty on a plaintext connection.
         */
        private function transportOf(Connection $connection): array {
            return [
                "cipher" => $this->sessionStatus($connection, "Ssl_cipher"),
                "version" => $this->sessionStatus($connection, "Ssl_version"),
            ];
        }

        // SHOW takes no placeholders, so the name is interpolated; it only
        // ever comes from the two literals above.
        private function sessionStatus(Connection $connection, string $variable): string {
            return $connection->exec("SHOW SESSION STATUS LIKE '$variable'")->resultToLine()["Value"];
        }

        private function ensureConnection(): void {
            // heartbeat() / disconnect() / direct $conn access blow up on
            // a fresh request that hasn't run any query yet (the typed
            // \mysqli property is uninitialized). One harmless SELECT
            // through exec() opens the connection via assertConnection.
            db()->exec("SELECT 1");
        }

        private function catchThrowableMessage(\Closure $action): void {
            try {
                $action();
                response()->json(["threw" => false]);
            } catch (\Throwable $e) {
                response()->json([
                    "threw" => true,
                    "type" => get_class($e),
                    "message" => $e->getMessage(),
                ]);
            }
        }

    }

?>
