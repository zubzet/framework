<?php
    namespace ZubZet\Framework\Database\Migration;

    use Cake\Database\Query;
    use ZubZet\Framework\QueryBuilder\CanBuildQuery;

    abstract class Seed {

        use CanBuildQuery;

        public $queries = [];

        abstract public function run();

        public function addQuery(Query $query) {
            $this->queries[] = $query;
        }

        public function insert($table, array $data) {
            $query = $this->dbInsert($table, $data);
            $this->addQuery($query);
        }

    }
