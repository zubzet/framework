<?php

    namespace ZubZet\Framework\Core;

    use ZubZet\Framework\ZubZet;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Core\CanRetrieveModel;
    use ZubZet\Framework\QueryBuilder\CanBuildQuery;


    use Cake\Database\Query;

    /**
     * Base class for all models. Models should inherit from this.
     * It holds utility classes for working with the database
     */
    class Model {

        use CanBuildQuery;
        use CanRetrieveModel;

        /**
         * @var Connection $z_db Reference to the database proxy
         */
        protected $z_db;

        /**
         * @var ZubZet $booter Reference to the booter
         */
        protected $booter;

        /**
         * @var int $lastInsertId Unused, never written. getInsertId() reads the ID from the connection instead.
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
        public function __construct(Connection $z_db, ZubZet $booter) {
            $this->z_db = $z_db;
            $this->booter = $booter;
        }

        /**
         * Executes a query as a prepared statement.
         * @param string|Query $query The query written as a prepared statement (with question marks), or a Query object.
         * @param string $types The types for the individual parameters (i for int, s for string...). Ignored for Query objects.
         * @param ...string $params Parameters to insert into the prepared statement. Ignored for Query objects.
         * @return Connection The Connection holding the result, for chaining
         */
        public function exec(string|Query $query, $types = "", $params = null): Connection {
            $this->z_db->callingModel = $this;
            if($query instanceof Query) return $this->z_db->execQuery($query);

            return $this->z_db->exec(...func_get_args());
        }

        /**
         * Returns the last insert id. Only inserts of the slow-query log are ignored, other log writes on the shared connection overwrite it.
         * @return int|string|null The ID of the dataset created in the last insert
         */
        public function getInsertId() {
            return $this->z_db->getInsertId();
        }

        /**
         * Converts the result of the last query into an array and returns it.
         * @param mixed[][] $out Optional, forwarded via func_get_args(): existing rows the result rows are appended to
         * @return mixed[][] Results of the last query as two-dimensional array
         * @throws \Error If the last query returned no result set (non-SELECT)
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
         * Selects a full table or specified fields of it and returns the result as two-dimensional array. Table and fields are not escaped, so never put user input in them.
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
         * @param string $where The where statement in the query, with "?" placeholders for values ("a = ? AND c = ?"...). Table, fields and where are not escaped, so never put user input in them.
         * @param string $types (Forwarded, optional) Types of the placeholder values, conform to prepared statements ("ssis")
         * @param mixed[] $values (Forwarded, optional) The values for the placeholders
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
         *
         * @deprecated since 1.2.0 Connections self-heal on use via the database
         *     layer's assertConnection(); manual keep-alive loops are no longer
         *     necessary. Remove your heartbeat() calls.
         *
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
    }

?>
