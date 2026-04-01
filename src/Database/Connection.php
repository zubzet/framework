<?php

    namespace ZubZet\Framework\Database;

    use Cake\Database\Driver\Mysql;
    use Cake\Database\Connection as QueryBuilderConnection;
    use Cake\Database\Query;
    use ZubZet\Framework\QueryBuilder\ZubZetValueBinder;

    class Connection {

        use Interaction;

        public QueryBuilderConnection $cakePHPDatabase;
        private \mysqli $conn;
        private \mysqli_stmt $stmt;
        public \z_framework $booter;

        public int $lastConnect;
        public int $lastHeartbeat;
        public int $connectTimeout;

        private string $user;
        private string $password;

        public function __construct(\z_framework &$booter) {
            $this->booter = $booter;

            $this->cakePHPDatabase = new QueryBuilderConnection([
                'driver' => Mysql::class,
            ]);

            $this->connectTimeout = $booter->req->getBooterSettings(
                "db_connection_timeout",
                default: 900,
            );

            $this->user = $booter->dbusername;
            $this->password = $booter->dbpassword;
        }

        private function connect() {
            // Make sure no previous connection exists
            $this->disconnect();

            // Connect to the database
            $this->conn = new \mysqli(
                $this->booter->dbhost,
                $this->user,
                $this->password,
                $this->booter->dbname,
            );

            // Set the connection charset
            $this->conn->set_charset("utf8mb4");

            // Remember the connection time
            $this->lastConnect = time();
        }

        public function switchUser(string $user, string $password): void {
            $this->user = $user;
            $this->password = $password;
            $this->connect();
        }

        public function assertConnection() {
            // No connection or connection lost
            if(!isset($this->conn) || $this->conn->connect_errno) {
                $this->connect();
                return;
            }

            // Check if we need to reconnect due to timeout
            if(!isset($this->lastConnect) || time() - $this->lastConnect >= $this->connectTimeout) {
                if(!isset($this->lastHeartbeat) || time() - $this->lastHeartbeat >= $this->connectTimeout) {
                    // Try a heartbeat to see if the connection is still alive
                    $connectionAlive = false;
                    try {
                        $connectionAlive = $this->heartbeat(waitForTimeout: false);
                    } catch(\Exception) {} finally {
                        if(!$connectionAlive) $this->connect();
                    }
                }
            }
        }

        /**
         * Executes a CakePHP Query object using ZubZet`s own value binder to extract the SQL and bindings, then executing it as a prepared statement
         *
         * @param Query $query The CakePHP Query object to execute
         * @return Connection Returning this for chaining
         */
        public function execQuery(Query $query) {
            // Use ZubZet`s own value binder to get the bindings in the format we need
            $zubzetValueBinder = new ZubZetValueBinder();

            // Get the SQL with placeholders and the bindings
            $sql = $query->sql($zubzetValueBinder);
            $bindings = $zubzetValueBinder->bindings();

            // If there are no bindings, we can execute the query directly
            if(empty($bindings)) return $this->exec($sql);

            // We need to convert the bindings to the format required by exec()
            $types = "";
            $values = [];
            foreach($bindings as $binding) {
                $value = $binding['value'];
                $values[] = $value;

                // Determine the type for the binding
                switch($binding['type']) {
                    case 'integer':
                    case 'biginteger':
                    case 'smallinteger':
                        $types .= 'i';
                        break;
                    case 'float':
                    case 'decimal':
                        $types .= 'd';
                        break;
                    case 'string':
                    case 'text':
                    default:
                        $types .= 's';
                        break;
                }
            }

            // Execute the query with the bindings
            return $this->exec($sql, $types, ...$values);
        }


        /**
         * Executes a query as prepared statement
         * @param string $query Query written as prepared statement (that thing with the question marks as placeholders)
         * @return Connection Returning this for chaining 
         */
        public function exec($query) {
            // Make sure a connection was made
            $this->assertConnection();

            $args = func_get_args();
            $preparationResult = $this->conn->prepare($query);
            if(is_bool($preparationResult)) {
                throw new \Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
            }

            $this->stmt = $preparationResult;

            if(count($args) > 1) {
                array_shift($args);
                $bindingResult = $this->stmt->bind_param(...$args);
                if(false === $bindingResult) {
                    throw new \Exception("SQL Binding Error: " . $this->conn->error . "\nQuery: " . $query);
                }
            }

            $executionResult = $this->stmt->execute();
            if(false === $executionResult) {
                throw new \Exception("SQL Execution Error: " . $this->stmt->error . "\nQuery: " . $query);
            }
            if($this->stmt->errno) {
                throw new \Exception("SQL STMT Error: " . $this->stmt->error . "\nQuery: " . $query);
            }

            $this->insertId = $this->conn->insert_id;

            $this->result = $this->stmt->get_result();

            $this->stmt->close();

            $this->lastHeartbeat = time();

            return $this;
        }

        public function executeMultiQuery(string $query, bool $throwOnFailure = true): bool {
            $this->assertConnection();

            if($this->conn->multi_query($query) === false) {
                if($throwOnFailure) throw new \Exception("SQL Multi-Query Error: " . $this->conn->error . "\nQuery: " . $query);
                return false;
            }

            do {
                if($this->conn->errno) {
                    if($throwOnFailure) throw new \Exception("SQL Multi-Query Error: " . $this->conn->error . "\nQuery: " . $query);

                    while($this->conn->more_results()) $this->conn->next_result();
                    return false;
                }

                if($result = $this->conn->store_result()) $result->free();
            } while($this->conn->more_results() && $this->conn->next_result());

            if($this->conn->errno) {
                if($throwOnFailure) throw new \Exception("SQL Multi-Query Error: " . $this->conn->error . "\nQuery: " . $query);
                return false;
            }

            $this->lastHeartbeat = time();
            return true;
        }

        public function getDatabaseConnection(): \mysqli {
            $this->assertConnection();
            return $this->conn;
        }

        /**
         * Run a very lightweight query to keep the connection alive
         * @return bool Was the heartbeat successful
         */
        public function heartbeat($waitForTimeout = true, $timeoutBuffer = 30): bool {
            if($waitForTimeout && isset($this->lastHeartbeat)) {
                // Only ping if the timeout was reached
                $timeSinceLastHeartbeat = time() - $this->lastHeartbeat;
                if($timeSinceLastHeartbeat < max(1, $this->connectTimeout - $timeoutBuffer)) {
                    return true;
                }
            }
            $this->lastHeartbeat = time();
            return $this->conn->ping();
        }

        /**
         * Disconnect from the database
         * @param boolean $forceClose Close the connection regardless of if it seems to be open
         * @return void
         */
        public function disconnect() {
            if(!isset($this->conn)) return;
            $this->conn->close();
        }

        /**
         * Closes the database connection on exit
         */
        public function __destruct() {
            $this->disconnect();
        }
    }

?>