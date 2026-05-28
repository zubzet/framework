<?php

    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\Message\Response;

    class LoggerController extends z_controller  {

        public function action_clearDatabaseLogs(Request $req, Response $res) {
            model("Logger")->clearLogs();
        }

        public function action_getDatabaseLogs(Request $req, Response $res) {
            echo json_encode(model("Logger")->getLogs());
        }

        public function action_log(Request $req, Response $res) {
            $name = $req->getGet("name", null);
            $method = $req->getGet("method", "info");

            logger($name)->$method("This is a test log for cypress e2e testing", ["stringInput" => "test","numberInput" => 123,"booleanInput" => true,"arrayInput" => [1, 2, 3],]);
        }

        public function action_multiLog(Request $req, Response $res) {
            logger()->info("first log", ["seq" => 1]);
            logger()->info("second log", ["seq" => 2]);
        }

        public function action_getTraceId(Request $req, Response $res) {
            logger()->info("with trace");
            echo Logger::getTraceId();
        }

        public function action_setTraceId(Request $req, Response $res) {
            Logger::setTraceId($req->getGet("trace"));
            logger()->info("after setTraceId");
        }

        public function action_context(Request $req, Response $res) {
            logger("ctx")->contextAdd(["x" => 1])->info("first");
            logger("ctx")->info("second");
        }

        public function action_contextInspect(Request $req, Response $res) {
            logger("ctx")
                ->contextAdd(["x" => 1])
                ->contextInspect(fn($c) => array_merge($c, ["y" => 2]))
                ->info("inspected");
        }

        public function action_contextMerge(Request $req, Response $res) {
            logger("source")->contextAdd(["x" => 1, "shared" => "from-source"]);
            logger("target")->contextAdd(["y" => 2, "shared" => "from-target"])
                ->contextMergeFrom("source")
                ->info("merged");
        }

        public function action_contextClear(Request $req, Response $res) {
            logger("cleared")->contextAdd(["x" => 1])->contextClear()->info("after clear");
        }

        public function action_contextMergeFromEmpty(Request $req, Response $res) {
            logger("x")->contextMergeFrom("");
        }

        public function action_contextMergeFromMissing(Request $req, Response $res) {
            logger("x")->contextMergeFrom("does-not-exist");
        }

        public function action_slowQuery(Request $req, Response $res) {
            db()->exec("SELECT SLEEP(0.5)");
        }

        public function action_slowInsertId(Request $req, Response $res) {
            // TRUNCATE resets AUTO_INCREMENT so the slow INSERT is deterministically id=3.
            db()->exec("TRUNCATE TABLE `slow_query`");
            db()->exec("INSERT INTO `slow_query` (`data`) VALUES ('a')");
            db()->exec("INSERT INTO `slow_query` (`data`) VALUES ('b')");

            // Slow INSERT crosses logger_slow_query_ms; nested INSERT into z_interaction_log
            // runs on the same Connection and would clobber insertId without the Checkpoint guard.
            db()->exec("INSERT INTO `slow_query` (`data`) VALUES (SLEEP(0.5))");

            echo json_encode(['insertId' => db()->getInsertId()]);
        }

        public function action_slowSelectResult(Request $req, Response $res) {
            $rows = db()->exec("SELECT 42 AS answer FROM (SELECT SLEEP(0.5)) t")->resultToArray();
            echo json_encode(['rows' => $rows]);
        }

        public function action_slowRequest(Request $req, Response $res) {
            usleep(800000);
        }

        public function action_slowRequestThenException(Request $req, Response $res) {
            usleep(800000);
            throw new \RuntimeException("boom after sleep");
        }

        public function action_deprecation(Request $req, Response $res) {
            trigger_error("old API usage", E_USER_DEPRECATED);
        }

        public function action_suppressedWarning(Request $req, Response $res) {
            @trigger_error("hidden warning", E_USER_WARNING);
        }

        public function action_uncaughtException(Request $req, Response $res) {
            throw new \RuntimeException("boom");
        }
    }
?>
