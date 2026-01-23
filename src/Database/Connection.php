<?php

    namespace ZubZet\Framework\Database;

    use Cake\Database\Driver\Mysql;
    use Cake\Database\Connection as QueryBuilderConnection;

    class Connection {

        use Interaction;

        public QueryBuilderConnection $cakePHPDatabase;
        private \mysqli $conn;
        private \mysqli_stmt $stmt;
        public \z_framework $booter;

        public int $lastConnect;
        public int $lastHeartbeat;
        public int $connectTimeout;

        public function __construct(\z_framework &$booter) {
            $this->booter = $booter;

            $this->cakePHPDatabase = new QueryBuilderConnection([
                'driver' => Mysql::class,
            ]);

            $this->connectTimeout = $booter->req->getBooterSettings(
                "db_connection_timeout",
                default: 900,
            );
        }

        private function connect() {
            // Make sure no previous connection exists
            $this->disconnect();

            // Connect to the database
            $this->conn = new \mysqli(
                $this->booter->dbhost,
                $this->booter->dbusername,
                $this->booter->dbpassword,
                $this->booter->dbname,
            );

            // Set the connection charset
            $this->conn->set_charset("utf8mb4");

            // Remember the connection time
            $this->lastConnect = time();
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
         * Executes a query as prepared statement
         * @param string $query Query written as prepared statement (that thing with the question marks as placeholders)
         * @return Connection Returning this for chaining 
         */
        public function exec($query) {
            // Make sure a connection was made
            $this->assertConnection();

            $args = func_get_args();
            $this->stmt = $this->conn->prepare($query);

            if(count($args) > 1) {
                array_shift($args);
                if (is_bool($this->stmt)) {
                    throw new \Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
                }
                $this->stmt->bind_param(...$args);
            }

            if(is_bool($this->stmt)) {
                throw new \Exception("SQL Error: " . $this->conn->error . "\nQuery: " . $query);
            }
            $this->stmt->execute();
            $this->insertId = $this->conn->insert_id;

            if($this->stmt->errno) {
                throw new \Exception("SQL Error: " . $this->stmt->error . "\nQuery: " . $query);
            }

            $this->result = $this->stmt->get_result();
            $this->stmt->close();

            $this->lastHeartbeat = time();

            return $this;
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