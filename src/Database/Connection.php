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

        /**
         * MySQL/MariaDB error codes that are safe to retry: the server has
         * already rolled the offending statement back, so re-running it does
         * not risk double-applying it. These are the transient contention
         * errors typical of busy single-node and cluster (Galera) setups.
         */
        private const RETRYABLE_ERROR_CODES = [
            1213, // Deadlock found when trying to get lock
            1205, // Lock wait timeout exceeded
        ];

        /** SQLSTATE values that are safe to retry (e.g. Galera certification conflicts). */
        private const RETRYABLE_SQL_STATES = [
            "40001", // Serialization failure
        ];

        /** Randomized backoff bounds (microseconds) slept between retry attempts. */
        private const RETRY_BACKOFF_MIN_US = 10_000;
        private const RETRY_BACKOFF_MAX_US = 50_000;

        /**
         * Client/server error codes meaning this connection cannot serve the
         * statement (endpoint moved by the mesh, node died, network blip, or
         * a Galera node refusing service while desynced as an SST/IST donor).
         * Recovered by reconnecting through the configured endpoint, which
         * routes to a usable node, and re-preparing. For 1047 the refusing
         * node never executed the statement, so that re-run is always safe;
         * the lost-acknowledgment caveat only applies to 2006/2013.
         */
        private const CONNECTION_LOSS_ERROR_CODES = [
            1047, // WSREP has not yet prepared node for application use
            2002, // Can't connect through socket / connection refused
            2003, // Can't connect to server on host
            2006, // Server has gone away
            2013, // Lost connection during query
        ];

        /**
         * Reconnect backoff grows with the attempt (attempt x step, capped) so
         * the budget spans a realistic failover window instead of burning out
         * in milliseconds while the mesh is still promoting a healthy node.
         */
        private const RECONNECT_BACKOFF_STEP_US = 400_000;
        private const RECONNECT_BACKOFF_CAP_US = 2_000_000;

        /** Seconds a single connect attempt may take before failing over. */
        private const CONNECT_TIMEOUT_SECONDS = 5;

        public QueryBuilderConnection $queryBuilderConnection;
        private \mysqli $conn;
        private \mysqli_stmt $stmt;
        public ZubZet $booter;

        public int $lastConnect;
        public int $lastHeartbeat;
        public int $connectTimeout;
        public int $maxRetries;

        private ?string $password;
        private ?string $user;
        private ?string $database;
        private bool $persistent;

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

            // Number of extra attempts made when a query fails with a transient,
            // cluster-related error (deadlock, lock-wait timeout, Galera
            // serialization conflict). 0 disables retries entirely.
            $maxRetries = config("db_max_retries", default: 3);
            if(!is_numeric($maxRetries)) {
                throw new \InvalidArgumentException("Config key 'db_max_retries' must be numeric, got: '$maxRetries'");
            }
            $this->maxRetries = max(0, (int) $maxRetries);

            $this->user = config("dbusername");
            $this->password = config("dbpassword");
            $this->database = config("dbname");
            $this->persistent = filter_var(config("db_persistent", default: false), FILTER_VALIDATE_BOOLEAN);
        }

        private function connect() {
            // Make sure no previous connection exists
            $this->disconnect();

            $endpoint = Endpoint::get();

            // Validate that all required config keys are present if using the database connection
            $missing = array_keys(array_filter([
                'dbhost' => $endpoint->host,
                'dbusername' => $this->user,
                'dbpassword' => $this->password,
                'dbname' => $this->database,
            ], fn($v) => empty($v)));

            if(!empty($missing)) {
                throw new \RuntimeException(
                    "Database connection requires valid configuration. Missing or empty config key(s): " . implode(', ', $missing)
                );
            }

            // Connect to the database. A bounded connect timeout keeps a
            // partitioned (SYN-blackholing) endpoint from hanging the request;
            // a down endpoint fails fast either way.
            $conn = mysqli_init();
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, self::CONNECT_TIMEOUT_SECONDS);

            // Transport encryption (db_ssl), off unless configured.
            $flags = $endpoint->applyTls($conn);

            // db_persistent: the "p:" prefix makes the PHP worker keep the
            // connection open across requests and skip the handshake. mysqli
            // resets a reused connection and replaces a dead one; opt-in
            // because a worker then stays on the node it first reached.
            // Prefixed on a local copy: connect() runs again on every
            // reconnect, and the endpoint is shared with the migrations.
            $host = $endpoint->host;
            if($this->persistent) $host = "p:" . $host;

            // PHP 8.1+ strict reporting throws a mysqli_sql_exception naming
            // the real failure; PHP 8.0 returns false and raises warnings
            // instead, leaving connect_error empty for TLS failures. Collect
            // the warnings and normalize, so both versions throw the same
            // exception with the same message and the caller's retry
            // classification sees the real error code.
            // Positional arguments because mysqli renamed its parameters
            // between PHP versions.
            $warnings = [];
            set_error_handler(function(int $severity, string $message) use (&$warnings): bool {
                $warnings[] = $message;
                return true;
            });
            try {
                $connected = $conn->real_connect(
                    $host,
                    $this->user,
                    $this->password,
                    $this->database,
                    $endpoint->port,
                    null,
                    $flags,
                );
            } finally {
                restore_error_handler();
            }
            if(false === $connected || $conn->connect_errno) {
                $message = (string) $conn->connect_error;
                if("" === $message) $message = (string) ($warnings[0] ?? "");
                throw new \mysqli_sql_exception($message, (int) $conn->connect_errno);
            }

            $this->conn = $conn;

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

            // The connection may have gone stale while idle; verify it with a
            // ping and reconnect if it no longer responds.
            $this->lastHeartbeat = time();
            if(!$this->pingConnection()) {
                $this->connect();
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
                $values[] = $binding['value'];

                // Determine the mysqli bind type; strings and unknown
                // types bind as strings, which MySQL coerces safely.
                $types .= match($binding['type']) {
                    'integer', 'biginteger', 'smallinteger' => 'i',
                    'float', 'decimal' => 'd',
                    default => 's',
                };
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

            $bindArgs = func_get_args();
            array_shift($bindArgs);

            $queryStart = microtime(true);
            $this->execWithRecovery($query, $bindArgs);
            $queryDuration = (microtime(true) - $queryStart) * 1000;

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
                array_slice($bindArgs, 1),
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

        /**
         * Runs the statement until it succeeds, recovering two failure
         * classes within the shared db_max_retries budget:
         *
         * - Transient contention (RETRYABLE_ERROR_CODES): the server already
         *   rolled the statement back, so re-running it on the kept
         *   connection after a short randomized backoff is safe.
         * - Connection loss (CONNECTION_LOSS_ERROR_CODES): reconnect through
         *   the configured endpoint, which routes to a usable node, then
         *   re-prepare and re-run. Documented caveat: if a write was applied
         *   but the connection died before the acknowledgment, the re-run
         *   applies it again; every exec() is auto-committed, so the exposure
         *   is a single statement.
         */
        private function execWithRecovery(string $query, array $bindArgs): void {
            $attempt = 0;
            $reconnect = false;

            while(true) {
                $failure = $this->attemptStatement($query, $bindArgs, $reconnect);
                if(is_null($failure)) return;

                if($this->shouldRetry($attempt, $failure["errorCode"], $failure["sqlState"])) {
                    $attempt++;
                    // A transient failure always leaves a live connection:
                    // connect() failures only carry connection-loss codes,
                    // never transient ones, so no reconnect is needed here.
                    $reconnect = false;
                    usleep(random_int(self::RETRY_BACKOFF_MIN_US, self::RETRY_BACKOFF_MAX_US));
                    continue;
                }

                if($this->shouldReconnect($attempt, $failure["errorCode"])) {
                    $attempt++;
                    $reconnect = true;
                    usleep($this->reconnectBackoffUs($attempt));
                    continue;
                }

                throw $failure["exception"];
            }
        }

        /**
         * Makes one attempt at the statement: reconnect when asked to,
         * prepare, bind, execute. Returns null on success and a failure
         * descriptor (see failure()) otherwise.
         *
         * mysqli reports failures by throwing under PHP 8.1+ strict
         * reporting and by return value on PHP 8.0; both are captured here,
         * reading the SQLSTATE from the handle that recorded it.
         */
        private function attemptStatement(string $query, array $bindArgs, bool $reconnect): ?array {
            try {
                $errorPrefix = "SQL Error";
                if($reconnect) $this->connect();

                $statement = $this->conn->prepare($query);
                if(is_bool($statement)) {
                    return $this->failure($errorPrefix, $this->conn->errno, $this->conn->sqlstate, $this->conn->error, $query);
                }
                $this->stmt = $statement;

                // PHP 8's mysqli throws ArgumentCountError / ValueError when
                // the type string or value count doesn't match the prepared
                // statement; bind_param() no longer returns false in any
                // reachable scenario.
                if(!empty($bindArgs)) $this->stmt->bind_param(...$bindArgs);

                $errorPrefix = "SQL Execution Error";
                if(!$this->stmt->execute()) {
                    return $this->failure($errorPrefix, $this->stmt->errno, $this->stmt->sqlstate, $this->stmt->error, $query);
                }
                return null;
            } catch(\mysqli_sql_exception $error) {
                return $this->failure($errorPrefix, (int) $error->getCode(), $this->sqlStateOf($error), $error->getMessage(), $query, $error);
            }
        }

        /**
         * Failure descriptor for one statement attempt, carrying what the
         * recovery decision needs and the ready-to-throw exception with the
         * historical prefix: "SQL Error" for connect and prepare failures,
         * "SQL Execution Error" for execute failures.
         */
        private function failure(string $errorPrefix, int $errorCode, ?string $sqlState, string $message, string $query, ?\mysqli_sql_exception $error = null): array {
            return [
                "errorCode" => $errorCode,
                "sqlState" => $sqlState,
                "exception" => new \Exception($errorPrefix . ": " . $message . "\nQuery: " . $query, 0, $error),
            ];
        }

        /**
         * Whether a just-failed query should be retried: there must be retry
         * budget left and the error must be a transient, cluster-related one.
         */
        private function shouldRetry(int $attempt, int $errorCode, ?string $sqlState): bool {
            return $attempt < $this->maxRetries && $this->isRetryable($errorCode, $sqlState);
        }

        /**
         * Whether a just-failed query should be recovered by reconnecting:
         * there must be retry budget left and the error must mean the
         * connection itself is gone.
         */
        private function shouldReconnect(int $attempt, int $errorCode): bool {
            return $attempt < $this->maxRetries
                && in_array($errorCode, self::CONNECTION_LOSS_ERROR_CODES, true);
        }

        /** Attempt-scaled backoff slept before a reconnect attempt. */
        private function reconnectBackoffUs(int $attempt): int {
            return min($attempt * self::RECONNECT_BACKOFF_STEP_US, self::RECONNECT_BACKOFF_CAP_US);
        }

        /**
         * Classifies an error as retryable. Retries are limited to transient
         * contention errors where the server already discarded the statement,
         * making a re-run safe. Pure decision, no side effects.
         */
        private function isRetryable(int $errorCode, ?string $sqlState): bool {
            if(in_array($errorCode, self::RETRYABLE_ERROR_CODES, true)) return true;
            if(!is_null($sqlState) && in_array($sqlState, self::RETRYABLE_SQL_STATES, true)) return true;
            return false;
        }

        /**
         * Reads the SQLSTATE from a thrown mysqli exception, where
         * mysqli_sql_exception::getSqlState() only exists on PHP 8.1+.
         * Exceptions without it are classified by error code alone; failures
         * reported by return value never reach this method, their SQLSTATE
         * is read off the failing handle in attemptStatement().
         */
        private function sqlStateOf(\mysqli_sql_exception $error): ?string {
            if(method_exists($error, "getSqlState")) return $error->getSqlState();
            return null;
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
         * Run a very lightweight query to keep the connection alive.
         *
         * @deprecated since 1.2.0 The connection now self-heals on use via
         *     assertConnection(), so a manual keep-alive loop is no longer
         *     necessary. Simply remove your heartbeat() calls and let queries
         *     transparently (re)connect as needed. Kept for backward
         *     compatibility with existing worker loops.
         *
         * @param bool $waitForTimeout Only ping if no heartbeat happened within the timeout window
         * @param int $timeoutBuffer Seconds subtracted from the timeout before a ping is forced
         * @return bool Whether a live connection responded. False when no
         *     connection has been opened yet (lazy loading) or the ping failed.
         */
        public function heartbeat($waitForTimeout = true, $timeoutBuffer = 30): bool {
            if($waitForTimeout && isset($this->lastHeartbeat)) {
                // Only ping if the timeout was reached
                $timeSinceLastHeartbeat = time() - $this->lastHeartbeat;
                if($timeSinceLastHeartbeat < max(1, $this->connectTimeout - $timeoutBuffer)) {
                    return true;
                }
            }

            // Connections are opened lazily on first use, so there may be
            // nothing to keep alive yet. Report the connection as not-alive
            // instead of fatally reading the uninitialized mysqli handle; this
            // lets callers decide to (re)connect on their own terms. Before
            // lazy loading this branch was unreachable because the connection
            // was always opened in the constructor.
            if(!isset($this->conn)) return false;

            $this->lastHeartbeat = time();
            return $this->pingConnection();
        }

        /**
         * Sends a lightweight query to check whether an already-established
         * connection is still responding. Assumes a connection has been opened.
         *
         * @return bool True if the server answered, false if the connection is dead
         */
        private function pingConnection(): bool {
            // mysqli::ping() is deprecated since PHP 8.4 (the reconnect
            // feature was removed in 8.2, leaving ping redundant). A
            // lightweight SELECT 1 round-trips the server identically and
            // surfaces a dead connection as a mysqli_sql_exception under
            // PHP 8.1+'s default MYSQLI_REPORT_STRICT.
            try {
                return $this->conn->query("SELECT 1") !== false;
            } catch(\mysqli_sql_exception) {
                return false;
            }
        }

        /**
         * Disconnect from the database
         * @return void
         */
        public function disconnect() {
            if(!isset($this->conn)) return;

            try {
                $this->conn->close();
            } catch(\Throwable) {}
            unset($this->conn);
        }

        /**
         * Closes the database connection on exit
         */
        public function __destruct() {
            $this->disconnect();
        }
    }

?>