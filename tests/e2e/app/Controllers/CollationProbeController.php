<?php

    use ZubZet\Framework\Database\Connection;

    // Probes for #184 and repair:database-collation; spec is tests/cypress/e2e/database/collation.cy.js
    class CollationProbeController extends z_controller {

        // Tables off the framework collation, and whether the permission UNION runs
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
                "permissions" => $permissions,
            ]);
        }

        // Reproduces #184 on a framework table, plus an application table created under another default
        public function action_misalign(Request $req, Response $res) {
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
