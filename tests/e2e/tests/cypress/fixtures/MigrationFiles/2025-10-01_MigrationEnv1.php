<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2025_10_01_MigrationEnv1 extends Migration {

        public function execute(): void {
            $this->setEnvironment("production");

            $this->tableAlter("migration_env")
                ->addColumn("description", "text", ["notnull" => false]);
        }
    }
?>