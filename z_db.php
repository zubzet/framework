<?php

    class z_db {
        
        private $conn;
        private $stmt;
        public $result;
        
        //When instanced, a db connection is given as a refrence
        function __construct(&$conn) {
            $this->conn = $conn;
        }

        //Custom prepared statement for sql queries
        function exec($query) {
            $args = func_get_args();
            $this->stmt = $this->conn->prepare($query);
            if (count($args) > 1) {
                array_shift($args);
                if (is_bool($this->stmt)) {
                    die("<b>SQL Error: </b>" . $this->conn->error . "<br><b>Query: </b>" . $query);
                } else {
                    $this->stmt->bind_param(...$args);
                }
            }
            if (is_bool($this->stmt)) {
                die("<b>SQL Error: </b>" . $this->conn->error . "<br><b>Query: </b>" . $query);
            } else {
                $this->stmt->execute();
            }
            if ($this->stmt->errno) {
                die("<b>SQL Error: </b>" . $this->stmt->error . "<br><b>Query: </b>" . $query);
            }
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

        function getTableWhere($table, $fields = "*", $where = "", $types="", $values = []) {
            $query = "SELECT $fields FROM $table WHERE $where";
            $this->exec($query, $types, ...$values);
            return $this->resultToArray();
        }

        function countTableEntries($table) {
            return $this->getFullTable($table, "COUNT(*) AS CNT")[0]["CNT"];
        }

        function countResults() {
            return $this->result->num_rows;
        }

        //May be dangerous. Check your input before using this!
        function checkIfUnique($table, $field, $value, $ignoreField = null, $ignoreValue = null) {
            if ($ignoreField == null) {
                $this->exec("SELECT " . $field . " FROM ". $table . " WHERE " . $field . " = ?", "s", $value);
            } else {
                $this->exec("SELECT " . $field . " FROM ". $table . " WHERE " . $field . " = ? AND " . $ignoreField . " <> ?", "ss", $value, $ignoreValue);
            }
            return ($this->result->num_rows == 0);
        }

        //May be dangerous. Check your input before using this!
        function checkIfExists($table, $field, $value) {
            $this->exec("SELECT " . $field . " FROM ". $table . " WHERE " . $field . " = ?", "s", $value);
            return ($this->result->num_rows > 0);
        }

    }

?>