<?php

    class z_loggerModel extends z_model  {

        public function appendEnvironment(&$logRecord) {
            $logRecord["environment"] = [
                "userId" => user()->userId,
                "execUserId" => user()->execUserId,
                "source" => request()->isCli() ? "cli" : "web",
            ];
        }

        public function log(array $logRecord) {
            $dataValue = [
                "message" => $logRecord['message'],
                "context" => $logRecord['context'],
                "level" => $logRecord['level'],
                "level_name" => $logRecord['level_name'],
                "channel" => $logRecord['channel'],
                "datetime" => $logRecord['datetime'],
                "extra" => $logRecord["extra"],
            ];

            $this->appendEnvironment($dataValue);

            $query = $this->dbInsert("z_interaction_log", [
                "text" => $logRecord['message'],
                "value" => json_encode($dataValue),
                "userId" => user()->userId,
                "userId_exec" => user()->execUserId,
            ]);

            $this->exec($query);
        }

    }

?>