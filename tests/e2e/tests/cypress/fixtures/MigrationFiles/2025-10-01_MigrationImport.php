<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2025_10_01_MigrationImport extends Migration {

        public function execute(): void {
            $table = $this->tableCreate("migration_import");
            $table->addColumn("id", "integer", ["autoincrement" => true]);
            $table->setPrimaryKey(["id"]);
            $table->addColumn("name", "string", ["length" => 255, "notnull" => true]);

            $this->run(
                "INSERT INTO migration_import (name) VALUES
                ('Test Entry 1'),
                ('Test Entry 2');"
            );
        }
    }
?>