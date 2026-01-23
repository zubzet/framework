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

    }

?>