<?php

    class z_model {

        protected $z_db;
        protected $booter;

        function __construct(&$z_db, $booter) {
            $this->z_db =& $z_db;
            $this->booter = $booter;
        }

        function exec() {
            return $this->z_db->exec(...func_get_args());
        }

        function getInsertId() {
            return $this->z_db->getInsertId(...func_get_args());
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
            $sql = "SELECT `id` FROM `interaction_log_category` WHERE LOWER(`name`) = LOWER(?)";
            $this->exec($sql, "s", $name);
            return $this->resultToLine()["id"];
        }

        function logAction($categoryId, $text, $value = null) {
            $user = $this->booter->user;

            $userId = $user->userId;
            $userId_exec = $user->execUserId;

            $sql = "INSERT INTO `interaction_log`(`categoryId`, `userId`, `userId_exec`, `text`, `value`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($sql, "iiisi", $categoryId, $userId, $userId_exec, $text, $value);
        }

        function logActionByCategory($categoryName, $text, $value = null) {
            $catId = $this->getLogCategoryIdByName($categoryName);
            $this->logAction($catId, $text, $value);
        }

    }

?>