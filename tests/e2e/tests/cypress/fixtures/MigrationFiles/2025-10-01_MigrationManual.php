<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2025_10_01_MigrationManual extends Migration {

        public function execute(): void {
            $this->setManual(true);

            $this->tableCreate("migration_manual")
                ->addColumn("description", "text", ["notnull" => false]);
        }
    }
?>