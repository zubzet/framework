<?php

    class ExampleModel extends z_model {

        public function getAll(): array {
            $sql = "SELECT *
                    FROM `example`
                    WHERE active = 1";

            return $this->exec($sql)->resultToArray();
        }

    }
?>
