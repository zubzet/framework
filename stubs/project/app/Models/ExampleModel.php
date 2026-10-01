<?php

    class ExampleModel extends z_model {

        public function getAll(): array {
            $sql = "SELECT *
                    FROM `example`";
            return $this->exec($sql)->resultToArray();
        }

    }
?>
