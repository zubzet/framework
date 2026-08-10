<?php

    // Exercises the transient-error retry loop in src/Database/Connection.php.
    // Row locks are node-local in Galera, so both conflict semantics are
    // driven deterministically by opening the lock-holding connection RELATIVE
    // to the node the framework connection landed on this request:
    // - same node: the framework UPDATE blocks on the held lock and fails
    //   with a lock-wait timeout (1205), which exec() must retry.
    // - different node: no lock exists there; the UPDATE certifies instantly
    //   and the lock holder is brute-force aborted by the replicated write.
    // Table test_retry is created in migrations/2026-07-02_DatabaseRetryProbe.sql;
    // cypress spec is tests/cypress/e2e/database/retry.cy.js.
    class DatabaseRetryProbeController extends z_controller {

        private const NODES = ["galera1", "galera2", "galera3"];

        public function action_lockWaitRetry(Request $req, Response $res) {
            // Hold the lock on the SAME node the framework connection uses,
            // guaranteeing the lock-wait path on every request.
            return $this->contendedUpdate($res, sameNode: true);
        }

        public function action_crossNodeConflict(Request $req, Response $res) {
            // Hold the lock on a DIFFERENT node: certification semantics.
            return $this->contendedUpdate($res, sameNode: false);
        }

        private function contendedUpdate(Response $res, bool $sameNode) {
            $frameworkNode = db()->exec("SELECT @@hostname AS h")->resultToArray()[0]["h"];

            // Cross-node: any REACHABLE peer works; a node can legitimately be
            // down or restarting right after the failover spec, so candidates
            // are probed in order instead of assumed available.
            $candidates = $sameNode
                ? [$frameworkNode]
                : array_values(array_diff(self::NODES, [$frameworkNode]));

            $lockNode = null;
            $lockHolder = null;
            foreach($candidates as $candidate) {
                $lockHolder = $this->tryConnect($candidate);
                if(is_null($lockHolder)) continue;
                $lockNode = $candidate;
                break;
            }
            if(is_null($lockHolder)) {
                throw new \RuntimeException("Probe found no reachable lock node among: " . implode(", ", $candidates));
            }

            $lockHolder->query("START TRANSACTION");
            $lockHolder->query("UPDATE test_retry SET v = v + 1 WHERE id = 1");

            // Framework connection: short timeout so each attempt fails fast.
            db()->getDatabaseConnection()->query("SET SESSION innodb_lock_wait_timeout = 1");

            $start = microtime(true);
            $errored = false;
            try {
                db()->exec("UPDATE test_retry SET v = v + 1 WHERE id = ?", "i", 1);
            } catch(\Throwable $e) {
                $errored = true;
            }
            $elapsedMs = (microtime(true) - $start) * 1000;

            // Release the lock so the request tears down cleanly. In the
            // cross-node case the framework's replicated write brute-force
            // aborts this transaction, and the connection's next statement
            // reports a deadlock; that is expected and safe to swallow.
            try {
                $lockHolder->query("ROLLBACK");
                $lockHolder->close();
            } catch(\Throwable $ignored) {}

            return $res->json([
                "frameworkNode" => $frameworkNode,
                "lockNode" => $lockNode,
                "errored" => $errored,
                "elapsedMs" => round($elapsedMs),
                "maxRetries" => db()->maxRetries,
            ]);
        }

        // Connection failures surface as warnings (PHP 8.0) or exceptions
        // (PHP 8.1+ strict reporting); both simply mean "not this node".
        private function tryConnect(string $node): ?\mysqli {
            try {
                $connection = @mysqli_connect($node, config("dbusername"), config("dbpassword"), config("dbname"));
            } catch(\Throwable $unavailable) {
                return null;
            }
            if($connection === false) return null;
            return $connection;
        }

    }

?>
