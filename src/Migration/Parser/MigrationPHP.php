<?php
    namespace ZubZet\Framework\Migration\Parser;

    use Doctrine\DBAL\Platforms\AbstractPlatform;
    use Doctrine\DBAL\Schema\Schema;
    use Exception;
    use ZubZet\Framework\Migration\Commands\Traits\DbalConnection;
    use ZubZet\Framework\Migration\Commands\Traits\Platform;
    use ZubZet\Framework\Migration\Migration;

    // Loads a SQL migration file
    class MigrationPHP {

        use Platform;
        use DbalConnection;

        public function extractInformation(string $filePath, array &$sqlBuffer, bool &$skip, string &$environment, bool &$manual): void {
            // Require the migration class (replace "-" with "_" for class name, ...e.g., "2025-11-15_test" -> "Migration_2025_11_15_test")
            $className = $this->requireMigrationClass($filePath);

            // Create DBAL connection and schema manager
            $connection = $this->createDbalConnection();
            $schemaManager = $connection->createSchemaManager();
            $platform = $this->getPlatform();

            // Retrieve current schema from the database
            $fromSchema = $schemaManager->introspectSchema();

            // Instantiate and run the migration (to generate SQL)
            $migrationInstance = new $className($fromSchema);
            $migrationInstance->execute();

            // Extract SQL statements from the migration
            $sqlBuffer = $this->extractSqlFromMigration($migrationInstance, $fromSchema, $schemaManager, $platform);

            // Get skip and environment settings from the migration
            $skip = $migrationInstance->skip;
            $environment = $migrationInstance->environment;
            $manual = $migrationInstance->manual;
        }

        private function extractSqlFromMigration(Migration $migration, Schema $fromSchema, $schemaManager, AbstractPlatform $platform): array {
            $sqlBuffer = [];
            $comparator = $schemaManager->createComparator();

            foreach($migration->getActions() as $action) {
                match ($action['type']) {
                    'create' => $this->createAction($sqlBuffer, $action, $platform),
                    'alter' => $this->alterAction($sqlBuffer, $action, $fromSchema, $platform, $comparator),
                    'drop' => $this->dropAction($sqlBuffer, $action, $platform),
                    'rename' => $this->renameAction($sqlBuffer, $action, $platform),
                    'run' => $sqlBuffer[] = $action['sql'],
                };

            }

            return $sqlBuffer;
        }

        // Requires the migration class from the given file and returns the class name
        private function requireMigrationClass(string $filePath): string {
            $base = str_replace('-', '_', pathinfo($filePath, PATHINFO_FILENAME));
            $className = "Migration_" . $base;

            require_once $filePath;

            if(!class_exists($className)) throw new Exception("Migration class '$className' not found in file '$filePath'");

            return $className;
        }

        private function createAction(&$sqlBuffer, $action, AbstractPlatform $platform) {
            $sqlBuffer = array_merge(
                $sqlBuffer,
                $platform->getCreateTableSQL($action['table'])
            );
        }

        private function alterAction(&$sqlBuffer, $action, Schema $fromSchema, AbstractPlatform $platform, $comparator) {
            $originalTable = $fromSchema->getTable($action['original_name']);
            $modifiedTable = $action['table'];

            $diff = $comparator->compareTables(
                $originalTable,
                $modifiedTable
            );

            if(!$diff->isEmpty()) {
                $sqlBuffer = array_merge(
                    $sqlBuffer,
                    $platform->getAlterTableSQL($diff)
                );
            }
        }

        private function dropAction(&$sqlBuffer, $action, AbstractPlatform $platform) {
            $sqlBuffer[] = $platform->getDropTableSQL($action['name']);
        }

        private function renameAction(&$sqlBuffer, $action, AbstractPlatform $platform) {
            $sqlBuffer[] = $platform->getRenameTableSQL($action['old'], $action['new']);
        }

    }
