<?php

use Doctrine\DBAL\Schema\Table;
use ZubZet\Framework\Migration\Commands\Traits\DbalConnection;
use ZubZet\Framework\Migration\Commands\Traits\Platform;
use ZubZet\Framework\Migration\Parser\MigrationFile;

    class z_migrationModel extends z_model {

        use Platform;
        use DbalConnection;

        // Check if migrations are locked
        public function isLocked(): bool {
            $connection = $this->createDbalConnection();
            $schemaManager = $connection->createSchemaManager();

            if(!$schemaManager->tablesExist(["z_migration_lock"])) {
                return false;
            }


            $query = $this->dbSelect("*", "z_migration_lock", [
                "is_locked" => 1,
            ]);

            $result = $this->exec($query)->resultToArray();
            return !empty($result);
        }

        // Ensure migration tables exist (z_migration_lock and z_version) (otherwise create them)
        public function ensureMigrationTablesExist(): void {
            $connection = $this->createDbalConnection();
            $schemaManager = $connection->createSchemaManager();
            $platform = $this->getPlatform();

            if(!$schemaManager->tablesExist(["z_migration_lock"])) {
                $table = new Table("z_migration_lock");
                $table->addColumn("id", "integer", ["autoincrement" => true]);
                $table->setPrimaryKey(["id"]);

                $table->addColumn("is_locked", "boolean", ["default" => 0]);
                $table->addColumn("locked_at", "timestamp");

                $sql = $platform->getCreateTableSQL($table);
                $this->exec($sql[0]);
            }

            if(!$schemaManager->tablesExist(["z_version"])) {
                $table = new Table("z_version");
                $table->addColumn("id", "integer", ["autoincrement" => true]);
                $table->setPrimaryKey(["id"]);

                $table->addColumn("migration_name", "string", ["length" => 255]);
                $table->addColumn("migration_date", "date");
                $table->addColumn("migration_version", "integer");
                $table->addColumn("active", "boolean", ["default" => true]);
                $table->addColumn("created", "timestamp");

                $sql = $platform->getCreateTableSQL($table);
                $this->exec($sql[0]);
            }
        }

        public function lockMigrations(): void {
            $insertQuery = $this->dbInsert("z_migration_lock", [
                "is_locked" => 1,
            ]);

            $this->exec($insertQuery);
        }

        public function unlockMigrations(): void {
            $deleteQuery = $this->dbDelete("z_migration_lock", [
                "is_locked" => 1,
            ]);

            $this->exec($deleteQuery);
        }


        public function getExecutedMigrations(): array {
            $migrations = [];
            try {
                $query = $this->dbSelect("*", "z_version")
                            ->orderDesc("migration_date")
                            ->orderDesc("migration_version");

                $migrations = db()->exec($query)->resultToArray();
            } catch(mysqli_sql_exception $e) {}

            return $migrations;
        }

        public function markAsExecuted(string $migrationName, $date, $version): void {
            $insertQuery = $this->dbInsert("z_version", [
                "migration_name" => $migrationName,
                "migration_date" => $date,
                "migration_version" => $version,
            ]);

            $this->exec($insertQuery);
        }

        // @TODO: abstract RecursiveIteratorIterator
        public function getFiles(string $path): array {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            $files = [];
            foreach($rii as $file) {
                if($file->isDir()) continue;
                $file = $file->getPathname();
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if("sql" !== $extension && "php" !== $extension) continue;
                $files[] = $file;
            }
            sort($files);
            return $files;
        }


        public function sortMigrations(array $files): array {
            $parsedFiles = [];

            foreach($files as $file) {
                $parsedFiles[] = $this->parseMigration($file);
            }

            usort($parsedFiles, function (MigrationFile $a, MigrationFile $b) {
                // Sort by date, then version, then name
                if($a->date != $b->date) {
                    return $a->date <=> $b->date;
                }
                if($a->version != $b->version) {
                    return $a->version <=> $b->version;
                }
                return strcmp($a->name, $b->name);
            });

            return $parsedFiles;
        }

        private function parseMigration(string $cleanFilename) {
            $filename = basename($cleanFilename);
            $segments = explode('_', $filename);

            // Migration filename must be in format YYYY-MM-DD_Name -> 2 Segments
            if(count($segments) < 2) {
                throw new InvalidArgumentException("Formatting error: '$filename'. Expected: YYYY-MM-DD_Name");
            }

            // First segment must be date
            $dateString = $segments[0];
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                throw new InvalidArgumentException("Syntax error: '$filename'. Date must be exactly YYYY-MM-DD.");
            }

            // Date validations
            $dateObj = DateTime::createFromFormat('Y-m-d', $dateString);
            if(!$dateObj) {
                throw new InvalidArgumentException("Syntax error: '$filename'. Invalid date format. Expected format: YYYY-MM-DD.");
            }

            $now = new DateTime('today');

            if($dateObj > $now) {
                throw new InvalidArgumentException("Future error: The date '$dateString' is in the future. Migrations can have at most today's date.");
            }

            if((int)$dateObj->format('Y') < 2000) {
                throw new InvalidArgumentException("History error: The year " . $dateObj->format('Y') . " is too far in the past.");
            }

            // Default version is 0 (if not specified)
            $version = 0;
            $nameStartIndex = 1;

            // Check if second segment is an integer -> version
            if(isset($segments[1]) && (filter_var($segments[1], FILTER_VALIDATE_INT))) {
                $version = $segments[1];
                $nameStartIndex = 2;
            }

            $nameParts = array_slice($segments, $nameStartIndex);
            $name = implode('_', $nameParts);

            if(empty($name)) throw new InvalidArgumentException("Name error: No name found in '$filename'.");

            return new MigrationFile($cleanFilename, $dateObj, $version, $name);
        }

    }

?>