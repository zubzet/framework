<?php
    /**
     * Holds the base model class
     */

    /**
     * Base class for all models. Models should inherit from this.
     * It holds utility classes for working with the database
     */
    class z_model {

        /**
         * @var z_db $z_db Reference to the database proxy
         */
        protected $z_db;

        /**
         * @var z_framework $booter Reference to the booter
         */
        protected $booter;

        /**
         * @var int $lastInsertId Holds the last id returned of an insert query. Does not change on logging
         */
        protected $lastInsertId;

        /**
         * Creates a z_model instance.
         * 
         * This constructor should only called from the booter. If you need a model, use $booter->getModel() instead.
         * 
         * @param z_db $z_db The database proxy class (Usally one lives in the booter)
         * @param z_framework $booter Booter object
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
         * @return z_model The model
         */
        public function getModel() {
            return $this->booter->getModel(...func_get_args());
        }

        /**
         * Executes a query as a prepared statement.
         * @param string $query The query written as a prepared statement (With the question marks).
         * @param string $types The types for the individual parameters (i for int, s for string...).
         * @param ...string $params to insert in the prepared statement
         * @return z_db Returning this for chaining 
         */
        function exec($query, $types = "", $params = null) {
            $res = $this->z_db->exec(...func_get_args());
            return $res;
        }

        /**
         * Returns the last insert id.
         * @return int The id of the in the last insert created dataset
         */
        function getInsertId() {
            return $this->z_db->getInsertId();
        }

        /**
         * Returns the rows affected. Usually used when making an update
         * @return int The id of the in the last insert created dataset
         */
        function getAffectedRows() {
            return $this->z_db->affectedRows;
        }

        /**
         * Converts the result of the last query into an array and returns it.
         * @return any[][] Results of the last query as two dimensional array
         */
        function resultToArray() {
            return $this->z_db->resultToArray(...func_get_args());
        }

        
        /**
         * Returns one line of the last query.
         * @return any[] Line of the last result
         */
        function resultToLine() {
            return $this->z_db->resultToLine(...func_get_args());
        }

        /**
         * Selects a full table or specified fields of it and returns the result as two dimensional array.
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formated as in an SQL query ("*", "a, b, c"...)
         * @return any[][] A two dimensional array with the results of the select statement
         */
        function getFullTable($table, $fields = "*") {
            return $this->z_db->getFullTable(...func_get_args());
        }

        /**
         * Selects a full table of specified fields of it filtered with an additional where statement. It returns the result as two dimensional array.
         * @param string $table Name of the table in the database
         * @param string $fields Fields to select. Formated as in a SQL query ("*", "a, b, c"...)
         * @param string $where The where statement in the query. Formated as in a SQL query (a = 4 AND c = 4...);
         * @return any[][] two dimensional array with table data
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
         * Returns the result of the last query.
         * @return any Result of the last query
         */
        function getResult() {
            return $this->z_db->result;
        }

        /**
         * Returns the number of results in the last query.
         * @return int Number of results in the last query
         */
        function countResults() {
            return $this->z_db->countResults(...func_get_args());
        }

        /**
         * Gets the id of a log category. If a category does not exists, this function will create it.
         * 
         * @param string $name Name of the category
         * @return int Id of the log category
         */
        function getLogCategoryIdByName($name) {
            if($this->z_db->booter->lite_mode) return;

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
         * @param int $categoryId Id of the category in the database
         * @param string $text Text
         * @param int $value Optional value
         */
        function logAction($categoryId, $text, $value = null) {
            if($this->z_db->booter->lite_mode) return;
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
         * @param int $categoryName Name of the category in the database.S
         * @param string $text Text
         * @param int $value Optional value
         */
        function logActionByCategory($categoryName, $text, $value = null) {
            $catId = $this->getLogCategoryIdByName($categoryName);
            $this->logAction($catId, $text, $value);
        }

    }

?>