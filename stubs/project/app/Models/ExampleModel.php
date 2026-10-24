<?php

    class ExampleModel extends z_model {

        public function getExamples(): array {
            $sql = "SELECT * FROM `example`";
            return $this->exec($sql)->resultToArray();
        }
    } 

?>