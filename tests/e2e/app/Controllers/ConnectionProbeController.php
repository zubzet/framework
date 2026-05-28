<?php

    use ZubZet\Framework\Database\Connection;

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
        // helpers
        // -------------------------------------------------------------

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
