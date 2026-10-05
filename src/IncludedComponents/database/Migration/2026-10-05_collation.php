<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2026_10_05_collation extends Migration {

        // Tables created with another collation than z_user, e.g. by a server default that changed
        public function execute(): void {
            $migration = model("z_migration");
            [$charset, $collation] = $migration->frameworkCollation();

            foreach($migration->misalignedFrameworkTables($collation) as $table) {
                $this->run("ALTER TABLE `$table` CONVERT TO CHARACTER SET $charset COLLATE $collation");
            }
        }
    }
?>
