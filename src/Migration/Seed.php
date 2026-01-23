<?php
    namespace ZubZet\Framework\Migration;

    use Cake\Database\Query;
    use ZubZet\Framework\Querybuilder\HelperTrait;

    abstract class Seed {

        use HelperTrait;

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
