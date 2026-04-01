<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2005_01_10_3_Syn_File extends Migration {

        public function execute(): void {
            $this->setEnvironment("production");

            $table = $this->tableCreate("migration_sync_5");
            $table->addColumn("id", "integer", ["autoincrement" => true]);
            $table->addColumn("data", "string", ["length" => 255, "notnull" => true]);
        }
    }
?>