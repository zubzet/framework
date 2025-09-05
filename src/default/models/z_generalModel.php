<?php
    /**
     * This file holds the general model
     */

    /**
     * The general model does general stuff that is needed everywhere
     */
    class z_generalModel extends z_model {

        /**
         * Gets a list of all languages in the database
         * @return array[] The language datasets
         */
        function getLanguageList() {
            return $this->getFullTable("z_language");
        }

        /**
         * Gets a single dataset of a language by its id
         * @param int $id ID of the language
         * @return array|null Language dataset
         */
        function getLanguageById($id) {
            $sql = "SELECT * FROM `z_language` WHERE `id`=?";
            $this->exec($sql, "i", $id);
            return $this->resultToLine();
        }

        /**
         * Gets the id of a language by its value
         * @param string $value The short form of the language (EN, DE_Formal...)
         * @return int The id of the language
         */
        function getLanguageByValue($value, $defaultLanguageId = 1) {
            $sql = "SELECT * FROM `z_language` WHERE `value`=?";
            $this->exec($sql, "s", $value);
            return $this->resultToLine()["id"] ?? $defaultLanguageId;
        }

        /**
         * Gets a unique reference
         * @return string A unique string
         */
        function getUniqueRef() {
            $ref = "";
            do {
                $ref = uniqid('', false);
            } while(!$this->checkUniqueRef($ref));
            $query = "INSERT INTO `z_uniqueref`(`ref`) VALUES (?)";
            $this->exec($query, "s", $ref);
            return $ref;
        }

        /**
         * Checks for a unique reference
         * @param string $ref The unique reference
         */
        function checkUniqueRef($ref) {
            $query = "SELECT * FROM `z_uniqueref` WHERE `ref`=?";
            $this->exec($query, "s", $ref);
            return $this->getResult()->num_rows == 0;
        }

    }

?>