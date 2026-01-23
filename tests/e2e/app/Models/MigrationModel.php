<?php

    class MigrationModel extends z_model  {

        public function checkImportSuccess() {
            $sql = "SELECT *
                    FROM `migration_import`";

            $migrationImport = $this->exec($sql)->resultToArray();

            $sql = "SHOW COLUMNS FROM `migration_import`";
            $columns = $this->exec($sql)->resultToArray();

            return [
                "entries" => $migrationImport,
                "columns" => $columns
            ];
        }

        public function checkPHPFiles() {
            $sql = "SHOW COLUMNS FROM `migration_php_import_renamed`";
            $schema = $this->exec($sql)->resultToArray();

            $sql = "SELECT EXISTS (
                        SELECT 1
                        FROM INFORMATION_SCHEMA.TABLES
                        WHERE TABLE_SCHEMA = 'datenbankname'
                        AND TABLE_NAME = 'tabellenname'
                    ) AS table_exists;";
            $exists = $this->exec($sql)->resultToLine();

            return [
                "schema" => $schema,
                "table_exists" => $exists["table_exists"] == 1 ? true : false
            ];
        }

        public function checkSkippedMigrations() {
            $sql = "SELECT *
                    FROM `migration_skip`";

            $this->exec($sql)->resultToArray();
        }

        public function checkMigrationEnv() {
            $sql = "SELECT *
                    FROM `migration_env`";

            $this->exec($sql)->resultToArray();
        }

        public function syncMigrations() {
            $sql = "SELECT *
                    FROM `z_version`";

            $versions = $this->exec($sql)->resultToArray();

            $sql = "SHOW TABLES";
            $tables = $this->exec($sql)->resultToArray();

            return [
                "versions" => $versions,
                "tables" => $tables
            ];
        }

        public function checkSeeding() {
            $sql = "SELECT *
                    FROM `migration_seed`";

            $seeds = $this->exec($sql)->resultToArray();

            return $seeds;
        }

        public function checkExternalMigrations() {
            $sql = "SELECT *
                    FROM `z_user`";

            $externals = $this->exec($sql)->resultToArray();

            return $externals;
        }

    }

?>