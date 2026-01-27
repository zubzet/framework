<?php

    namespace ZubZet\Framework\Core;

    use ZubZet\Framework\ZubZet;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Querybuilder\CanBuildQuery;

    use Cake\Database\Query;

    /**
     * Base class for all models. Models should inherit from this.
     * It holds utility classes for working with the database
     */
    class Model {

        use CanBuildQuery;

        /**
         * @var Connection $z_db Reference to the database proxy
         */
        protected $z_db;

        /**
         * @var ZubZet $booter Reference to the booter
         */
        protected $booter;

        /**
         * @var int $lastInsertId Holds the last ID returned from an insert query. Does not change during logging.
         */
        protected $lastInsertId;

        /**
         * Creates a z_model instance.
         * 
         * This constructor should only be called from the booter. If you need a model, use $booter->getModel() instead.
         * 
         * @param Connection $z_db The database proxy class (Usually one lives in the booter)
         * @param ZubZet $booter Booter object
         */
        public function __construct(&$z_db, $booter) {
            $this->z_db =& $z_db;
            $this->booter = $booter;
            $this->lastInsertId;
        }

        /**
         * Returns a model
         * @param string $model Name of the model
         * @param string $dir Set this when the model is stored in a specific directory
         * @return Model The model
         */
        public function getModel() { 
            return model(...func_get_args());
        }

        /**
         * Executes a query as a prepared statement.
         * @param string $query The query written as a prepared statement (with question marks).
         * @param string $types The types for the individual parameters (i for int, s for string...).
         * @param ...string $params Parameters to insert into the prepared statement
         * @return Connection Returning this for chaining 
         */
        public function exec(string|Query $query, $types = "", $params = null): Connection {
            if ($query instanceof Query) {
                $sql = $query->sql();

                $bindings = $query->getValueBinder()->bindings();

                $sql = preg_replace('/:\w+/', '?', $sql);

                $types = '';
                $values = [];

                foreach ($bindings as $binding) {
                    $values[] = $binding['value'];

                    switch ($binding['type']) {
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

                if($types === '') {
                    return $this->z_db->exec($sql);
                }

                return $this->z_db->exec($sql, $types, ...$values);
            }

            return $this->z_db->exec(...func_get_args());
        }

        /**
         * Returns the last insert id. Ignores inserts done by log.
         * @return int The ID of the dataset created in the last insert
         */
        public function getInsertId() {
            return $this->z_db->getInsertId();
        }

        /**
         * Converts the result of the last query into an array and returns it.
         * @return mixed[][] Results of the last query as two-dimensional array
         */
        public function resultToArray(): array {
            return $this->z_db->resultToArray(...func_get_args());
        }
        
        /**
         * Returns one line of the last query.
         * @return array|null Line of the last result
         */
        public function resultToLine(): ?array {
            return $this->z_db->resultToLine();
        }

        /**
         * Selects a full table or specified fields of it and returns the result as two-dimensional array.
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formatted as in an SQL query ("*", "a, b, c"...)
         * @return array[] A two-dimensional array with the results of the select statement
         */
        public function getFullTable($table, $fields = "*") {
            return $this->z_db->getFullTable(...func_get_args());
        }

        /**
         * Selects a full table or specified fields, filtered with an additional where statement. It returns the result as a two-dimensional array.
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formatted as in an SQL query ("*", "a, b, c"...)
         * @param string $where The where statement in the query. Formatted as in an SQL query ("a = 4 AND c = 4"...);
         * @return array[] Two-dimensional array with table data
         */
        public function getTableWhere($table, $fields, $where) {
            return $this->z_db->getTableWhere(...func_get_args());
        }

        /**
         * Returns the number of datasets in a table.
         * @param string $table Name of the table in the database
         * @return int Number of datasets in the specified table
         */
        public function countTableEntries($table) {
            return $this->z_db->countTableEntries(...func_get_args());
        }

        /**
         * Runs a very lightweight query to keep the connection alive.
         * @param bool $waitForTimeout If set to true, only ping if no other query has been made within the timeout period
         * @return void
         */
        public function heartbeat($waitForTimeout = true) {
            $this->z_db->heartbeat(...func_get_args());
        }

        /**
         * Returns the result of the last query.
         * @return null|bool|\mysqli_result Result of the last query
         */
        public function getResult() {
            return $this->z_db->result;
        }

        /**
         * Returns the number of results in the last query.
         * @return int Number of results in the last query
         */
        public function countResults() {
            return $this->z_db->countResults();
        }

        /**
         * Gets the ID of a log category. If a category does not exist, this function will create it.
         * 
         * @param string $name Name of the category
         * @return int ID of the log category
         */
        public function getLogCategoryIdByName($name) {
            $sql = "SELECT `id` FROM `z_interaction_log_category` WHERE LOWER(`name`) = LOWER(?)";
            $this->exec($sql, "s", $name);
            if ($this->countResults() > 0) {
                return $this->resultToLine()["id"];
            }

            $sql = "INSERT INTO `z_interaction_log_category`(`name`) VALUES (?)";
            $this->exec($sql, "s", $name);
            return $this->getInsertId();

        }

        /**
         * Logs an action.
         * 
         * Does not increase the insertId.
         * 
         * @param int $categoryId ID of the category in the database
         * @param string $text Text
         * @param int $value Optional value
         */
        public function logAction($categoryId, $text, $value = null) {
            $user = $this->booter->user;
            $insertId = $this->getInsertId(); //Store to restore later

            $userId = $user->userId;
            $userId_exec = $user->execUserId;

            $sql = "INSERT INTO `z_interaction_log`(`categoryId`, `userId`, `userId_exec`, `text`, `value`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($sql, "iiiss", $categoryId, $userId, $userId_exec, $text, $value);
            $this->lastInsertId = $insertId; //Ignore this insert because we won't need this id anyways
        }

        /**
         * Logs an action.
         *
         * Does not increase the insertId. If the category does not exist, it will be created.
         *
         * @param string $categoryName Name of the category in the database.
         * @param string $text Text
         * @param int $value Optional value
         */
        public function logActionByCategory($categoryName, $text, $value = null) {
            $catId = $this->getLogCategoryIdByName($categoryName);
            $this->logAction($catId, $text, $value);
        }
    }

?>
