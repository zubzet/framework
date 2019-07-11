<?php

    class GeneralModel extends z_model {

        /**
         * Gets a list of all languages in the database
         */
        function getLanguageList() {
            return $this->getFullTable("language");
        }

        function getLanguageById($id) {
            $sql = "SELECT * FROM `language` WHERE `id`=?";
            $this->exec($sql, "i", $id);
            return $this->resultToLine();
        }

        function getLanguageByValue($value) {
            $sql = "SELECT * FROM `language` WHERE `value`=?";
            $this->exec($sql, "s", $value);
            return $this->resultToLine()["id"];
        }

        function getCountryList() {
            return $this->getFullTable("country");
        }

        function getUniqueRef() {
            $ref = "";
            do {
                $ref = uniqid('', false);
            } while(!$this->checkUniqueRef($ref));
            $query = "INSERT INTO `uniqueref`(`ref`) VALUES (?)";
            $this->exec($query, "s", $ref);
            return $ref;
        }

        function checkUniqueRef($ref) {
            $query = "SELECT * FROM `uniqueref` WHERE `ref`=?";
            $this->exec($query, "s", $ref);
            return $this->getResult()->num_rows == 0;
        }

    }

?>