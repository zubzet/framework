<?php

    class z_generalModel extends z_model {

        /**
         * Gets a list of all languages in the database
         */
        function getLanguageList() {
            return $this->getFullTable("z_language");
        }

        function getLanguageById($id) {
            $sql = "SELECT * FROM `z_language` WHERE `id`=?";
            $this->exec($sql, "i", $id);
            return $this->resultToLine();
        }

        function getLanguageByValue($value) {
            $sql = "SELECT * FROM `z_language` WHERE `value`=?";
            $this->exec($sql, "s", $value);
            return $this->resultToLine()["id"];
        }

        function getUniqueRef() {
            $ref = "";
            do {
                $ref = uniqid('', false);
            } while(!$this->checkUniqueRef($ref));
            $query = "INSERT INTO `z_uniqueref`(`ref`) VALUES (?)";
            $this->exec($query, "s", $ref);
            return $ref;
        }

        function checkUniqueRef($ref) {
            $query = "SELECT * FROM `z_uniqueref` WHERE `ref`=?";
            $this->exec($query, "s", $ref);
            return $this->getResult()->num_rows == 0;
        }

    }

?>