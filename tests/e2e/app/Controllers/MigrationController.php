<?php

    class MigrationController extends z_controller  {


        // Checks via Cypress if the Migration Import was successful
        public function action_checkImport(Request $req, Response $res) {
            $entries = $req->getModel("Migration")->checkImportSuccess();

            print_r(json_encode($entries));
        }


        public function action_checkPHPFiles(Request $req, Response $res) {
            $columns = $req->getModel("Migration")->checkPHPFiles();

            print_r(json_encode($columns));
        }

        public function action_checkSkippedMigrations(Request $req, Response $res) {
            $req->getModel("Migration")->checkSkippedMigrations();
        }

        public function action_checkEnvMigrations(Request $req, Response $res) {
            $req->getModel("Migration")->checkMigrationEnv();
        }

        public function action_syncMigrations(Request $req, Response $res) {
            $files = $req->getModel("Migration")->syncMigrations();
            print_r(json_encode($files));
        }

        public function action_checkSeeding(Request $req, Response $res) {
            $entries = $req->getModel("Migration")->checkSeeding();

            print_r(json_encode($entries));
        }

        // Probe for the custom DBAL TimeStamp type. Used by
        // migration/timestamp-type.cy.js to verify that the migration in
        // app/Database/migrations/2026-05-08_TimeStampType.php produced a
        // TIMESTAMP column via TimeStamp::getSQLDeclaration().
        public function action_checkTimestampType(Request $req, Response $res) {
            $row = db()->exec(
                "SELECT DATA_TYPE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'z_test_timestamp_type'
                   AND COLUMN_NAME = 'created'"
            )->resultToLine();

            return $res->json([
                "found" => !empty($row),
                "dataType" => $row["DATA_TYPE"] ?? null,
                "columnType" => $row["COLUMN_TYPE"] ?? null,
            ]);
        }

        // Test fixtures for db:unlock-migration. Used by migration/system.cy.js.
        public function action_lockMigration(Request $req, Response $res) {
            model("z_migration")->ensureMigrationTablesExist();
            model("z_migration")->lockMigrations();
            echo json_encode(['locked' => model("z_migration")->isLocked()]);
        }

        public function action_isMigrationLocked(Request $req, Response $res) {
            echo json_encode(['locked' => model("z_migration")->isLocked()]);
        }

    }

?>