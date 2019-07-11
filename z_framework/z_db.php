<?php

    class z_db {
        
        private $conn;
        private $stmt;
        public $result;
        public $rqclient;
        
        //When instanced, a db connection is given as a refrence
        function __construct(&$conn, &$rqclient) {
            $this->conn = $conn;
            $this->rqclient =& $rqclient;
        }

        //Custom prepared statement for sql queries
        function exec($query) {
            $args = func_get_args();
            $this->stmt = $this->conn->prepare($query);
            if (count($args) > 1) {
                array_shift($args);
                $this->stmt->bind_param(...$args);
            }
            $this->stmt->execute();
            $this->result = $this->stmt->get_result();
            $this->stmt->close();
        }

        function getInsertId() {
            return $this->conn->insert_id;
        }

        //Put the full result in an array
        function resultToArray($out = []) {
            while ($row = $this->result->fetch_assoc()) {
                array_push($out, $row);             
            }
            return $out;
        }

        function resultToLine() {
            return $this->result->fetch_assoc();
        }

        function getFullTable($table, $fields = "*") {
            $query = "SELECT $fields FROM $table";
            $this->exec($query);
            return $this->resultToArray();
        }

        function countTableEntries($table) {
            return $this->getFullTable($table, "COUNT(*) AS CNT")[0]["CNT"];
        }

        function countResults() {
            return $this->result->num_rows;
        }

    }

?>