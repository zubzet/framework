<?php
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2025_10_01_MigrationDrySideEffect extends Migration {

        public function execute(): void {
            User::add("migration_dry_side_effect@cypress.test", null);
        }
    }
?>
