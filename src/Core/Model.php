<?php

    namespace ZubZet\Framework\Core;

    use Cake\Database\Query;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\ZubZet;

    /**
     * Holds the base model class
     */

    /**
     * Base class for all models. Models should inherit from this.
     * It holds utility classes for working with the database
     */
    class Model {

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
        function __construct(&$z_db, $booter) {
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
        function exec(string|Query $query, $types = "", $params = null): Connection {
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
        function getInsertId() {
            return $this->z_db->getInsertId();
        }

        /**
         * Converts the result of the last query into an array and returns it.
         * @return mixed[][] Results of the last query as two-dimensional array
         */
        function resultToArray(): array {
            return $this->z_db->resultToArray(...func_get_args());
        }
        
        /**
         * Returns one line of the last query.
         * @return array|null Line of the last result
         */
        function resultToLine(): ?array {
            return $this->z_db->resultToLine();
        }

        /**
         * Selects a full table or specified fields of it and returns the result as two-dimensional array.
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formatted as in an SQL query ("*", "a, b, c"...)
         * @return array[] A two-dimensional array with the results of the select statement
         */
        function getFullTable($table, $fields = "*") {
            return $this->z_db->getFullTable(...func_get_args());
        }

        /**
         * Selects a full table or specified fields, filtered with an additional where statement. It returns the result as a two-dimensional array.
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formatted as in an SQL query ("*", "a, b, c"...)
         * @param string $where The where statement in the query. Formatted as in an SQL query ("a = 4 AND c = 4"...);
         * @return array[] Two-dimensional array with table data
         */
        function getTableWhere($table, $fields, $where) {
            return $this->z_db->getTableWhere(...func_get_args());
        }

        /**
         * Returns the number of datasets in a table.
         * @param string $table Name of the table in the database
         * @return int Number of datasets in the specified table
         */
        function countTableEntries($table) {
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
        function getResult() {
            return $this->z_db->result;
        }

        /**
         * Returns the number of results in the last query.
         * @return int Number of results in the last query
         */
        function countResults() {
            return $this->z_db->countResults();
        }

        /**
         * Gets the ID of a log category. If a category does not exist, this function will create it.
         * 
         * @param string $name Name of the category
         * @return int ID of the log category
         */
        function getLogCategoryIdByName($name) {
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
        function logAction($categoryId, $text, $value = null) {
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
        function logActionByCategory($categoryName, $text, $value = null) {
            $catId = $this->getLogCategoryIdByName($categoryName);
            $this->logAction($catId, $text, $value);
        }


        /**
         * Create a new SelectQuery instance for the CakePHP\Database Connection.
         *
         * @param \Cake\Database\ExpressionInterface|callable|array|string $fields fields to be added to the list.
         * @param array|string $table The table or list of tables to query.
         * @param array<string, string> $types Associative array containing the types to be used for casting.
         * @return \Cake\Database\Query\SelectQuery
        */
        public function dbSelect(
            $fields = [],
            $table = [],
            array $types = []
        ) {
            return $this->getQueryBuilder()->selectQuery($fields, $table, $types);
        }

        /**
         * Create a new UpdateQuery instance for the CakePHP\Database Connection.
         *
         * @param \Cake\Database\ExpressionInterface|string|null $table The table to update rows of.
         * @param array $values Values to be updated.
         * @param array $conditions Conditions to be set for the update statement.
         * @param array<string, string> $types Associative array containing the types to be used for casting.
         * @return \Cake\Database\Query\UpdateQuery
        */
        public function dbUpdate(
            $table = null,
            array $values = [],
            array $conditions = [],
            array $types = []
        ) {
            return $this->getQueryBuilder()->updateQuery($table, $values, $conditions, $types);
        }

        /**
         * Create a new DeleteQuery instance for the CakePHP\Database Connection.
         *
         * @param string|null $table The table to delete rows from.
         * @param array $conditions Conditions to be set for the delete statement.
         * @param array<string, string> $types Associative array containing the types to be used for casting.
         * @return \Cake\Database\Query\DeleteQuery
        */
        public function dbDelete(?string $table = null, array $conditions = [], array $types = []) {
            return $this->getQueryBuilder()->deleteQuery($table, $conditions, $types);
        }

        /**
         * Create a new InsertQuery instance for the CakePHP\Database Connection.
         *
         * @param string|null $table The table to insert rows into.
         * @param array $values Associative array of column => value to be inserted.
         * @param array<int|string, string> $types Associative array containing the types to be used for casting.
         * @return \Cake\Database\Query\InsertQuery
        */
        public function dbInsert(?string $table = null, array $values = [], array $types = []) {
            return $this->getQueryBuilder()->insertQuery($table, $values, $types);
        }

        /*
        * Returns the Query Builder instance for the CakePHP\Database Connection.
        *
        * @return \Cake\Database\Connection The Query Builder instance
        */
        public function getQueryBuilder() {
            return $this->z_db->cakePHPDatabase;
        }
    }

?>
