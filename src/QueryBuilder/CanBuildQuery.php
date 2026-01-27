<?php
    namespace ZubZet\Framework\QueryBuilder;

    trait CanBuildQuery {

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
            return db()->cakePHPDatabase;
        }
    }