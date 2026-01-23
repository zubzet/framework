<?php
    namespace ZubZet\Framework\Migration;

    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\DBAL\Schema\Table;

    abstract class Migration {

        public bool $manual = false;
        public bool $skip = false;
        public string $environment = "default";

        public $fromSchema;

        protected $actions = [];

        public function __construct(Schema $fromSchema) {
            $this->fromSchema = $fromSchema;
        }

        public function &tableCreate(string $name): Table {
            $table = new Table($name);

            $this->actions[] = [
                'type' => 'create',
                'table' => $table
            ];

            return $this->actions[count($this->actions)-1]['table'];
        }

        public function &tableAlter(string $name): Table {
            $existingTable = $this->fromSchema->getTable($name);
            $newTable = clone $existingTable;

            $this->actions[] = [
                'type' => 'alter',
                'table' => $newTable,
                'original_name' => $name
            ];

            return $this->actions[count($this->actions)-1]['table'];
        }

        public function tableDrop(string $name) {
            $this->actions[] = [
                'type' => 'drop',
                'name' => $name
            ];
        }

        public function tableRename(string $oldName, string $newName) {
            $this->actions[] = [
                'type' => 'rename',
                'old' => $oldName,
                'new' => $newName
            ];
        }

        public function run(string $sql){
            $this->actions[] = [
                'type' => 'run',
                'sql' => $sql
            ];
        }

        public function getActions(): array {
            return $this->actions;
        }

        public abstract function execute(): void;

        public function skip(): void {
            $this->skip = true;
        }

        public function setEnvironment(string $env): void {
            $this->environment = $env;
        }

        public function setManual(bool $manual): void {
            $this->manual = $manual;
        }
    }