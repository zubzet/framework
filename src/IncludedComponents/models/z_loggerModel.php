<?php

use Monolog\LogRecord;

    class z_loggerModel extends z_model  {

        public function log(array $logRecord) {
            $text = \sprintf(
                "[%s.%s] %s\n",
                $logRecord['channel'],
                $logRecord['level_name'],
                $logRecord['message'],
            );

            $value = json_encode($logRecord['context']);

            $query = $this->dbInsert("z_interaction_log", [
                "text" => $text,
                "value" => $value,
                "userId" => user()->userId,
                "userId_exec" => user()->execUserId,
            ]);

            $this->exec($query);
        }

    }

?>