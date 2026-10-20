<?php

    use ZubZet\Framework\Database\Connection;

    // Probes for #184 and repair:database-collation; spec is tests/cypress/e2e/database/collation.cy.js
    class CollationProbeController extends z_controller {

        // Framework tables and all tables off the framework collation, the database default, and whether the permission UNION runs
        public function action_state(Request $req, Response $res) {
            try {
                model("z_user")->getPermissionsByUserId(1);
                $permissions = "ok";
            } catch(\Throwable $e) {
                $permissions = $e->getMessage();
            }

            return $res->json([
                "collation" => Connection::COLLATION,
                "misaligned" => model("z_migration")->tablesNotInCollation(Connection::COLLATION),
                "misalignedAll" => model("z_migration")->tablesNotInCollation(Connection::COLLATION, true),
                "databaseCollation" => db()->exec(
                    "SELECT DEFAULT_COLLATION_NAME AS name FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()"
                )->resultToLine()["name"],
                "permissions" => $permissions,
            ]);
        }

        // Reproduces #184 on a framework table, plus an application table created under another default and a database default to match
        public function action_misalign(Request $req, Response $res) {
            db()->exec("ALTER DATABASE `" . config("dbname") . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            db()->exec("ALTER TABLE `z_user_permission` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            db()->exec("DROP TABLE IF EXISTS `collation_probe`");
            db()->exec(
                "CREATE TABLE `collation_probe` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
                    `name` VARCHAR(255) NOT NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
            );

            return $this->action_state($req, $res);
        }
    }

?>
