<?php

    class z_model {

        protected $z_db;
        protected $booter;
        protected $lastInsertId;

        function __construct(&$z_db, $booter) {
            $this->z_db =& $z_db;
            $this->booter = $booter;
            $this->lastInsertId;
        }

        function exec() {
            $res = $this->z_db->exec(...func_get_args());
            $this->lastInsertId = $this->z_db->getInsertId(...func_get_args());
            return $res;
        }

        function getInsertId() {
            return $this->lastInsertId;
        }

        function resultToArray() {
            return $this->z_db->resultToArray(...func_get_args());
        }

        function resultToLine() {
            return $this->z_db->resultToLine(...func_get_args());
        }

        function getFullTable() {
            return $this->z_db->getFullTable(...func_get_args());
        }

        function getTableWhere() {
            return $this->z_db->getTableWhere(...func_get_args());
        }

        function countTableEntries() {
            return $this->z_db->countTableEntries(...func_get_args());
        }

        function getResult() {
            return $this->z_db->result;
        }

        function countResults() {
            return $this->z_db->countResults(...func_get_args());
        }

        function getLogCategoryIdByName($name) {
            $sql = "SELECT `id` FROM `z_interaction_log_category` WHERE LOWER(`name`) = LOWER(?)";
            $this->exec($sql, "s", $name);
            if ($this->countResults() > 0) {
                return $this->resultToLine()["id"];
            }

            $sql = "INSERT INTO `z_interaction_log_category`(`name`) VALUES (?)";
            $this->exec($sql, "s", $name);
            return $this->getInsertId();

        }

        /**
         * Logs an action
         * @param int $categoryId Id of the category in the database
         * @param String $text Text
         * @param int $value Optional value
         */
        function logAction($categoryId, $text, $value = null) {
            $user = $this->booter->user;
            $insertId = $this->getInsertId(); //Store to restore later

            $userId = $user->userId;
            $userId_exec = $user->execUserId;

            $sql = "INSERT INTO `z_interaction_log`(`categoryId`, `userId`, `userId_exec`, `text`, `value`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($sql, "iiiss", $categoryId, $userId, $userId_exec, $text, $value);
            $this->lastInsertId = $insertId; //Ignore this insert because we won't need this id anyways
        }

        function logActionByCategory($categoryName, $text, $value = null) {
            $catId = $this->getLogCategoryIdByName($categoryName);
            $this->logAction($catId, $text, $value);
        }

    }

?>