<?php

    class z_model {

        protected $z_db;

        function __construct(&$z_db) {
            $this->z_db =& $z_db;
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

        function countTableEntries() {
            return $this->z_db->countTableEntries(...func_get_args());
        }

        function getResult() {
            return $this->z_db->result;
        }

        function countResults() {
            return $this->z_db->countResults(...func_get_args());
        }

        function getRQClient() {
            if (!isset($this->z_db->rqclient["id"])) return ["id" => -1, "id_exec" => -1];
            return $this->z_db->rqclient;
        }

        function getLogCategoryIdByName($name) {
            $sql = "SELECT `id` FROM `interaction_log_category` WHERE LOWER(`name`) = LOWER(?)";
            $this->exec($sql, "s", $name);
            return $this->resultToLine()["id"];
        }

        function logAction($categoryId, $text, $value = null) {
            $employeeId = (isset($this->getRQClient()["id"]) ? $this->getRQClient()["id"] : null);
            $employeeId_exec = $this->getRQClient()["id_exec"];
            $sql = "INSERT INTO `interaction_log`(`categoryId`, `employeeId`, `employeeId_exec`, `text`, `value`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($sql, "iiisi", $categoryId, $employeeId, $employeeId_exec, $text, $value);
        }

    }

?>