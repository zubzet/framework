<?php

    namespace ZubZet\Framework\Database;

    use ZubZet\Framework\ZubZet;
    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\QueryBuilder\ZubZetValueBinder;
    use ZubZet\Framework\Support\Checkpoint\CanCheckpoint;
    use ZubZet\Framework\Support\Checkpoint\Checkpointable;
    use ZubZet\Framework\Support\Checkpoint\IncludeInCheckpoint;
    use ZubZet\Framework\ErrorHandling\DebugBar\DebugBarBridge;

    use Cake\Database\Query;
    use Cake\Database\Driver\Mysql;
    use Cake\Database\Connection as QueryBuilderConnection;

    class Connection implements Checkpointable {

        use Interaction;
        use CanCheckpoint;

        public QueryBuilderConnection $queryBuilderConnection;
        private \mysqli $conn;
        private \mysqli_stmt $stmt;
        public ZubZet $booter;

        public int $lastConnect;
        public int $lastHeartbeat;
        public int $connectTimeout;

        private ?string $host;
        private ?string $password;
        private ?string $user;
        private ?string $database;

        public function __construct() {
            $this->booter = zubzet();

            $this->queryBuilderConnection = new QueryBuilderConnection([
                'driver' => Mysql::class,
            ]);

            // Check if timeout config is a valid number
            $timeout = config("db_connection_timeout", default: 900);
            if(!is_numeric($timeout)) {
                throw new \InvalidArgumentException("Config key 'db_connection_timeout' must be numeric, got: '$timeout'");
            }
            $this->connectTimeout = (int) $timeout;

            $this->host = config("dbhost");
            $this->user = config("dbusername");
            $this->password = config("dbpassword");
            $this->database = config("dbname");
        }

        private function connect() {
            // Make sure no previous connection exists
            $this->disconnect();

            // Validate that all required config keys are present if using the database connection
            $missing = array_keys(array_filter([
                'dbhost' => $this->host,
                'dbusername' => $this->user,
                'dbpassword' => $this->password,
                'dbname' => $this->database,
            ], fn($v) => empty($v)));

            if(!empty($missing)) {
                throw new \RuntimeException(
                    "Database connection requires valid configuration. Missing or empty config key(s): " . implode(', ', $missing)
                );
            }

            // Connect to the database
            $this->conn = new \mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->database,
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
            if(isset($this->lastConnect) && time() - $this->lastConnect < $this->connectTimeout) {
                return;
            }

            // Check if we recently did a heartbeat, if so we can skip the check
            if(isset($this->lastHeartbeat) && time() - $this->lastHeartbeat < $this->connectTimeout) {
                return;
            }

            // Try a heartbeat to see if the connection is still alive
            $connectionAlive = false;

            try {
                $connectionAlive = $this->heartbeat(waitForTimeout: false);
            } catch(\Exception) {} finally {
                if(!$connectionAlive) {
                    $this->connect();
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

            // PHP 8.1+ defaults mysqli to MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT,
            // which throws mysqli_sql_exception before prepare() / execute() can
            // return false. Catch and rethrow with the framework's error prefix
            // so the API contract is identical across PHP versions.
            try {
                $preparationResult = $this->conn->prepare($query);
            } catch(\mysqli_sql_exception $e) {
                throw new \Exception("SQL Error: " . $e->getMessage() . "\nQuery: " . $query, 0, $e);
            }
            if(is_bool($preparationResult)) {
                throw new \Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
            }

            $this->stmt = $preparationResult;

            if(count($args) > 1) {
                array_shift($args);
                // PHP 8's mysqli throws ArgumentCountError / ValueError when
                // the type string or value count doesn't match the prepared
                // statement; bind_param() no longer returns false in any
                // reachable scenario, so a wrapping `if(false === ...)` check
                // would be dead code.
                $this->stmt->bind_param(...$args);
            }

            $queryStart = microtime(true);
            try {
                $executionResult = $this->stmt->execute();
            } catch(\mysqli_sql_exception $e) {
                throw new \Exception("SQL Execution Error: " . $e->getMessage() . "\nQuery: " . $query, 0, $e);
            }
            $queryDuration = (microtime(true) - $queryStart) * 1000;

            if(false === $executionResult) {
                throw new \Exception("SQL Execution Error: " . $this->stmt->error . "\nQuery: " . $query);
            }

            $this->insertId = $this->conn->insert_id;

            $this->result = $this->stmt->get_result();

            $rowCount = $this->result instanceof \mysqli_result
                ? $this->result->num_rows
                : $this->conn->affected_rows;

            $this->stmt->close();

            $this->lastHeartbeat = time();

            // Collect the query for the debug bar
            DebugBarBridge::collectQuery(
                $query,
                $queryDuration / 1000,
                $rowCount,
                array_slice($args, 1),
                $this->callingModel,
            );

            $slowQueryThreshold = config("logger_slow_query_ms", default: 300);
            if(!is_null($slowQueryThreshold) && $queryDuration >= $slowQueryThreshold) {
                // The slow-query log itself runs an INSERT through this same Connection,
                // which clobbers every #[IncludeInCheckpoint] property. Reentrancy into the
                // logger is prevented by DatabaseLogger's own guard.
                $checkpoint = $this->checkpointCurrentState(attributeClass: IncludeInCheckpoint::class);
                try {
                    logger(Logger::ZUBZET)->warning(LogEventType::SLOW_QUERY, [
                        'duration_ms' => round($queryDuration, 2),
                        'query' => $query,
                    ]);
                } finally {
                    $checkpoint->restore();
                }
            }

            return $this;
        }

        public function executeMultiQuery(string $query, bool $throwOnFailure = true): bool {
            $this->assertConnection();

            // PHP 8.1+ defaults mysqli to STRICT reporting, which makes
            // multi_query() / next_result() throw mysqli_sql_exception instead
            // of returning false / setting errno. Catch any of them and route
            // back through the framework's throwOnFailure contract.
            try {
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
            } catch(\mysqli_sql_exception $e) {
                if($throwOnFailure) throw new \Exception("SQL Multi-Query Error: " . $e->getMessage() . "\nQuery: " . $query, 0, $e);
                while($this->conn->more_results()) {
                    try {
                        $this->conn->next_result();
                    } catch(\mysqli_sql_exception) {}
                }
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