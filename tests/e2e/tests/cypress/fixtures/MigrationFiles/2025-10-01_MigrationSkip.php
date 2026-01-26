<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2025_10_01_MigrationSkip extends Migration {

        public function execute(): void {
            $this->skip();

            $this->tableAlter("migration_skip")
                ->addColumn("description", "text", ["notnull" => false]);
        }
    }
?>