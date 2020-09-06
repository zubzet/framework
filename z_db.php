<?php
    /**
     * z_db is used as a proxy for all database actions
     */

    /**
     * Proxy for all database access. Also holds utility functions
     */
    class z_db {
        
        /**
         * @var mysqli $conn Connection to the database
         */
        private $conn;

        /**
         * @var mysqli_stmt $stmt Prepared statement object used for queries
         */
        private $stmt;

        /**
         * @var any $result Result of the last query
         */
        public $result;

        /**
         * @var z_framework $booter Reference to the booter
         */
        public $booter;
        
        /**
         * When instanced, a db connection is given as a refrence
         */
        function __construct(&$conn, &$booter) {
            $this->conn = $conn;
            $this->booter = $booter;
        }

        /**
         * Executes a query as prepared statement
         * @param string $query Query written as prepared statement (that thing with the question marks as placeholders)
         */
        function exec($query) {
            $args = func_get_args();
            $this->stmt = $this->conn->prepare($query);
            if (count($args) > 1) {
                array_shift($args);
                if (is_bool($this->stmt)) {
                    throw new Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
                } else {
                    $this->stmt->bind_param(...$args);
                }
            }
            if (is_bool($this->stmt)) {
                throw new Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
            } else {
                $this->stmt->execute();
            }
            if ($this->stmt->errno) {
                throw new Exception("SQL Error: " . $this->stmt->error . "\nQuery: " . $query);
            }
            $this->result = $this->stmt->get_result();
            $this->stmt->close();
            return $this;
        }

        /**
         * Returns the id of the last inserted element
         * @return int Id of the last insterted element
         */
        function getInsertId() {
            return $this->conn->insert_id;
        }

        /**
         * Converts the result of the last query into an array and returns it
         * @return any[][] Results of the last query as two dimensional array
         */
        function resultToArray($out = []) {
            while ($row = $this->result->fetch_assoc()) {
                array_push($out, $row);             
            }
            return $out;
        }

        /**
         * Returns one line of the last query
         * @return any[] Line of the last result
         */
        function resultToLine() {
            return $this->result->fetch_assoc();
        }

        /**
         * Selects a full table or specified fields of it and returns the result as two dimensional array
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formated as in an SQL query ("*", "a, b, c"...)
         * @return any[][] A two dimensional array with the results of the select statement
         */
        function getFullTable($table, $fields = "*") {
            $query = "SELECT $fields FROM $table";
            $this->exec($query);
            return $this->resultToarray();
        }

        /**
         * Selects a full table of specified fields of it filtered with an additional where statement. It returns the result as two dimensional array
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formated as in a SQL query ("*", "a, b, c"...)
         * @param string $where The where statement in the query. Formated as in a SQL query (a = 4 AND c = 4...);
         * @param string $types String with the types. Conform to prepared statements ("ssis")
         * @param any[] $values The values to insert in the prepared statement
         * @return any[][] two dimensional array with table data
         */
        function getTableWhere($table, $fields = "*", $where = "", $types="", $values = []) {
            $query = "SELECT $fields FROM $table WHERE $where";
            $this->exec($query, $types, ...$values);
            return $this->resultToarray();
        }
        
        /**
         * Returns the number of datasets in a table
         * @param string $table Name of the table in the database
         * @return int Number of datasets in the specified table
         */
        function countTableEntries($table) {
            return $this->getFullTable($table, "COUNT(*) AS CNT")[0]["CNT"];
        }

        /**
         * Returns the number of results in the last query
         * @return int Number of results in the last query
         */
        function countResults() {
            return $this->result->num_rows;
        }

        /**
         * Checks if a value is already in a table. Can also ignore a dataset.
         * 
         * Table and field names are inserted unescaped. Check your input.
         * 
         * @param string $table Name of the table to check in
         * @param string $field Field to check in
         * @param any $value Value to check for
         * @param string $ignoreField field of a dataset to ignore
         * @param string $ignoreValue value of the in the argument before defined field of the dataset to ignore
         * @return bool True when not exists
         */
        function checkIfUnique($table, $field, $value, $ignoreField = null, $ignoreValue = null) {
            if ($ignoreField == null) {
                $this->exec("SELECT `" . $field . "` FROM `". $table . "` WHERE `" . $field . "` = ?", "s", $value);
            } else {
                $this->exec("SELECT `" . $field . "` FROM `". $table . "` WHERE `" . $field . "` = ? AND `" . $ignoreField . "` <> ?", "ss", $value, $ignoreValue);
            }
            return ($this->result->num_rows == 0);
        }

        /**
         * Checks if a value exists in a table.
         * 
         * Table and field names are inserted unescaped. Check your input.
         * 
         * @param string $table Name of the table to check in
         * @param string $field Name of the field in that a value should exist
         * @param any $value Value to check for
         * @return bool True when exists
         */
        function checkIfExists($table, $field, $value) {
            $this->exec("SELECT `" . $field . "` FROM `". $table . "` WHERE `" . $field . "` = ?", "s", $value);
            return ($this->result->num_rows > 0);
        }

    }

?>