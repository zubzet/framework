<?php

    class CoreModel extends z_model {

        public function getData() {
            return "Test Model Call";
        }

        public function insertData($testName) {
            $sql = "INSERT INTO `model_test_insert` (`value`)
                    VALUES (?)";
            return $this->exec($sql, "s", $testName);
        }

        public function getModelTestsInsert() {
            $sql = "SELECT * 
                    FROM `model_test_insert`";
            return $this->exec($sql)->resultToArray();
        }

        public function getModelTestsLine() {
            $sql = "SELECT *
                    FROM `model_test_select`";
            return $this->exec($sql)->resultToLine();
        }

        public function getModelCount() {
            $sql = "SELECT *
                    FROM `model_test_select`";
            return $this->exec($sql)->countResults();
        }

        public function getModelTestsArray() {
            $sql = "SELECT *
                    FROM `model_test_select`";
            return $this->exec($sql)->resultToArray();
        }

        public function getModelLastId() {
            $sql = "INSERT INTO `model_test_lastid` (`value`)
                    VALUES ('LastId')";
            return $this->exec($sql)->getInsertId();
        }
    } 

?>