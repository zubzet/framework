<?php
    use ZubZet\Framework\Migration\Migration;

    class Migration_2025_10_01_MigrationEnv extends Migration {

        public function execute(): void {
            $this->setEnvironment("production");

            $table = $this->tableCreate("migration_env");
            $table->addColumn("id", "integer", ["autoincrement" => true]);
            $table->setPrimaryKey(["id"]);
            $table->addColumn("name", "string", ["length" => 255, "notnull" => true]);
        }
    }
?>