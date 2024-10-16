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
         * @var null|bool|mysqli_result $result Result of the last query
         */
        public $result;

        /**
         * @var z_framework $booter Reference to the booter
         */
        public $booter;

        /**
         * @var int $insertId Last insert id
         */
        public $insertId;

        /**
         * @var int $lastConnect Unix timestamp of the last database connect
         */
        public $lastConnect;

        /**
         * @var int $lastConnect Unix timestamp of the last database connect
         */
        public $lastHeartbeat;

        /**
         * @var int $connectTimeout Database connection timeout in seconds. Defaults to 0.5 hours
         */
        public int $connectTimeout;
        
        /**
         * When instanced, a db connection is given as a refrence
         */
        public function __construct(&$booter) {
            $this->booter = $booter;
            $this->connectTimeout = $booter->req->getBooterSettings(
                "db_connection_timeout",
                $useDefault = true,
                900,
            );
            $this->connect(firstConnection: true);
        }

        /**
         * Closes the database connection on exit
         */
        public function __destruct() {
            $this->disconnect();
        }

        /**
         * Make a connection to the database
         * @param string $charset
         * @param bool $firstConnection Determine if this a reconnect
         * @return void
         */
        public function connect($charset = "utf8mb4", $firstConnection = false) {
            // Try to close the connection if it exists
            if(!$firstConnection) {
                $this->disconnect();
            }

            // Connect to the database
            $this->conn = new mysqli(
                $this->booter->dbhost,
                $this->booter->dbusername,
                $this->booter->dbpassword,
                $this->booter->dbname
            );

            // Set the connection charset
            $this->conn->set_charset($charset);

            // Remember the connection time
            $this->lastConnect = time();
        }

        public function assertConnection() {
            try {
                $this->heartbeat(waitForTimeout: false);
            } catch(\Exception) {
                $this->connect();
            }
        }

        /**
         * Disconnect from the database
         * @param boolean $forceClose Close the connection regardless of if it seems to be open
         * @return void
         */
        public function disconnect($forceClose = false) {
            if($forceClose || $this->heartbeat()) {
                $this->conn->close();
            }
        }

        /**
         * Executes a query as prepared statement
         * @param string $query Query written as prepared statement (that thing with the question marks as placeholders)
         * @return z_db Returning this for chaining 
         */
        public function exec($query) {
            // Make sure a connection was made and has not timed out
            if(!isset($this->lastConnect) || time() - $this->lastConnect >= $this->connectTimeout) {
                if(!isset($this->lastHeartbeat) || time() - $this->lastHeartbeat >= $this->connectTimeout) {
                    $this->assertConnection();
                }
            }

            $args = func_get_args();
            $this->stmt = $this->conn->prepare($query);

            if(count($args) > 1) {
                array_shift($args);
                if (is_bool($this->stmt)) {
                    throw new Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
                }
                $this->stmt->bind_param(...$args);
            }

            if(is_bool($this->stmt)) {
                throw new Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
            }
            $this->stmt->execute();
            $this->insertId = $this->conn->insert_id;

            if($this->stmt->errno) {
                throw new Exception("SQL Error: " . $this->stmt->error . "\nQuery: " . $query);
            }

            $this->result = $this->stmt->get_result();
            $this->stmt->close();

            $this->lastHeartbeat = time();

            return $this;
        }

        /**
         * Run a very lightweight query to keep the connection alive
         * @return void
         */
        public function heartbeat($waitForTimeout = true) {
            if($waitForTimeout) {
                // Only ping if the timeout 
                if(isset($this->lastHeartbeat) && time() - $this->lastHeartbeat < max(1, $this->connectTimeout - 30)) {
                    return;
                }
            }
            $this->lastHeartbeat = time();
            $this->exec("SELECT 1");
        }

        /**
         * Returns the id of the last inserted element
         * @return int Id of the last inserted element
         */
        public function getInsertId() {
            return $this->insertId;
        }

        /**
         * Converts the result of the last query into an array and returns it
         * @return mixed[][] Results of the last query as two dimensional array
         */
        public function resultToArray($out = []): array {
            while ($row = $this->result->fetch_assoc()) {
                array_push($out, $row);
            }
            return $out;
        }

        /**
         * Converts the result of the last query into a grouped array
         * @param string $groupBy The field, by which the array is grouped by
         * @param string $subElement If set, the only a sub element of the grouped element is returned
         * @return any[groupBy][] Results of the last query as two dimensional array with the index as thr groupBy value
         */
        public function mergeAsGroup($groupBy, $subElement = null) {
            $elements = $this->resultToArray();
            $groups = [];
            foreach($elements as $element) {
                if (!isset($groups[$element[$groupBy]])) {
                    $groups[$element[$groupBy]] = [];
                }
                if(isset($subElement)) {
                    $groups[$element[$groupBy]][] = $element[$subElement];
                    continue;
                }
                $groups[$element[$groupBy]][] = $element;
            }
            return $groups;
        }

        /**
         * Returns one line of the last query
         * @return mixed[] Line of the last result
         */
        public function resultToLine(): ?array {
            return $this->result->fetch_assoc();
        }

        /**
         * Selects a full table or specified fields of it and returns the result as two dimensional array
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formatted as in an SQL query ("*", "a, b, c"...)
         * @return array[] A two dimensional array with the results of the select statement
         */
        public function getFullTable($table, $fields = "*") {
            $sql = "SELECT $fields FROM $table";
            $this->exec($sql);
            return $this->resultToArray();
        }

        /**
         * Selects a full table of specified fields of it filtered with an additional where statement. It returns the result as two dimensional array
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formatted as in a SQL query ("*", "a, b, c"...)
         * @param string $where The where statement in the query. Formatted as in a SQL query (a = 4 AND c = 4...);
         * @param string $types String with the types. Conform to prepared statements ("ssis")
         * @param mixed[] $values The values to insert in the prepared statement
         * @return array[] two dimensional array with table data
         */
        public function getTableWhere($table, $fields = "*", $where = "", $types="", $values = []) {
            $sql = "SELECT $fields FROM $table WHERE $where";
            $this->exec($sql, $types, ...$values);
            return $this->resultToArray();
        }
        
        /**
         * Returns the number of datasets in a table
         * @param string $table Name of the table in the database
         * @return int Number of datasets in the specified table
         */
        public function countTableEntries($table) {
            return $this->getFullTable($table, "COUNT(*) AS CNT")[0]["CNT"];
        }

        /**
         * Returns the number of results in the last query
         * @return int Number of results in the last query
         */
        public function countResults() {
            return $this->result->num_rows;
        }

        /**
         * Checks if a value is already in a table. Can also ignore a dataset.
         * 
         * Table and field names are inserted unescaped. Check your input.
         * 
         * @param string $table Name of the table to check in
         * @param string $field Field to check in
         * @param mixed $value Value to check for
         * @param string $ignoreField field of a dataset to ignore
         * @param string $ignoreValue value of the in the argument before defined field of the dataset to ignore
         * @return bool True when not exists
         */
        public function checkIfUnique($table, $field, $value, $ignoreField = null, $ignoreValue = null) {
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
         * @param mixed $value Value to check for
         * @return bool True when exists
         */
        public function checkIfExists($table, $field, $value) {
            $this->exec("SELECT `" . $field . "` FROM `". $table . "` WHERE `" . $field . "` = ?", "s", $value);
            return ($this->result->num_rows > 0);
        }

    }

?>