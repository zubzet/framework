<?php

    // Exercises the transient-error retry loop in src/Database/Connection.php.
    // A second, independent connection holds an exclusive row lock without
    // committing, so the framework connection's UPDATE on the same row blocks
    // and fails with a lock-wait timeout (1205) - a retryable error. Table
    // z_test_retry is created in migrations/2026-07-02_DatabaseRetryProbe.sql;
    // cypress spec is tests/cypress/e2e/database/retry.cy.js.
    class DatabaseRetryProbeController extends z_controller {

        // Holds a row lock on a separate connection, then updates the same row
        // through the framework connection with a 1-second lock-wait timeout.
        // With db_max_retries > 0 the framework re-attempts the update several
        // times before giving up, so the elapsed time exceeds a single
        // timeout, proving the retry loop engaged. The lock is never released
        // within the request, so the query is expected to ultimately fail.
        public function action_lockWaitRetry(Request $req, Response $res) {
            // Separate connection acquires and holds an exclusive row lock.
            $lockHolder = new z_db();
            $lockHolder->exec("START TRANSACTION");
            $lockHolder->exec("UPDATE z_test_retry SET v = v + 1 WHERE id = ?", "i", 1);

            // Framework connection: short timeout so each attempt fails fast.
            db()->getDatabaseConnection()->query("SET SESSION innodb_lock_wait_timeout = 1");

            $start = microtime(true);
            $errored = false;
            try {
                db()->exec("UPDATE z_test_retry SET v = v + 1 WHERE id = ?", "i", 1);
            } catch(\Throwable $e) {
                $errored = true;
            }
            $elapsedMs = (microtime(true) - $start) * 1000;

            // Release the lock so the request tears down cleanly.
            $lockHolder->exec("ROLLBACK");

            return $res->json([
                "errored" => $errored,
                "elapsedMs" => round($elapsedMs),
                "maxRetries" => db()->maxRetries,
            ]);
        }

    }

?>
