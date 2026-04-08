<?php

    class LoggerModel extends z_model  {

        public function clearLogs() {
            $query = $this->dbDelete("z_interaction_log");
            $this->exec($query);
        }

        public function getLogs() {
            $query = $this->dbSelect("*", "z_interaction_log");
            return $this->exec($query)->resultToArray();
        }
    }

?>