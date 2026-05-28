<?php
    use ZubZet\Framework\Database\Migration\Migration;

    /**
     * Permanent test fixture: exercises the framework's custom TimeStamp DBAL
     * type (src/Database/Migration/Type/TimeStamp.php). Doctrine resolves
     * `'timestamp'` to that type when generating CREATE TABLE SQL, so this
     * migration's apply path runs both `getSQLDeclaration()` and `getName()`.
     */
    class Migration_2026_05_08_TimeStampType extends Migration {

        public function execute(): void {
            $table = $this->tableCreate("z_test_timestamp_type");
            $table->addColumn("id", "integer", ["autoincrement" => true]);
            $table->addColumn("created", "timestamp");
            $table->setPrimaryKey(["id"]);
        }
    }
?>
