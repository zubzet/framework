<?php
    use ZubZet\Framework\Migration\Migration;

    class Migration_2025_10_01_MigrationPHPImport extends Migration {

        public function execute(): void {
            $this->tableAlter("migration_php_import")
                ->addColumn("description", "text", ["notnull" => false]);

            $this->tableRename("migration_php_import", "migration_php_import_renamed");

            $this->tableDrop("migration_php_remove");
        }
    }
?>