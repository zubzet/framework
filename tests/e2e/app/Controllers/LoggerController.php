<?php

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

            logger($name)->$method("This is a test log for cypress e2e testing", [
                "stringInput" => "test",
                "numberInput" => 123,
                "booleanInput" => true,
                "arrayInput" => [1, 2, 3],
            ]);
        }
    }
?>