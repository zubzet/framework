<?php

    class CollationProbeController extends z_controller {

        // The collation framework tables should have, those that differ, and whether the permission UNION runs
        public function action_state(Request $req, Response $res) {
            $migration = model("z_migration");
            [, $collation] = $migration->frameworkCollation();

            try {
                model("z_user")->getPermissionsByUserId(1);
                $permissions = "ok";
            } catch(\Throwable $e) {
                $permissions = $e->getMessage();
            }

            return $res->json([
                "collation" => $collation,
                "misaligned" => $migration->misalignedFrameworkTables($collation),
                "permissions" => $permissions,
            ]);
        }

        // Reproduces #184: a framework table created under another default collation
        public function action_misalign(Request $req, Response $res) {
            [, $collation] = model("z_migration")->frameworkCollation();
            $other = "utf8mb4_general_ci" === $collation ? "utf8mb4_unicode_ci" : "utf8mb4_general_ci";
            db()->exec("ALTER TABLE `z_user_permission` CONVERT TO CHARACTER SET utf8mb4 COLLATE $other");

            // Lets the next db:migrate run the repair migration again
            db()->exec("DELETE FROM `z_version` WHERE `migration_name` = '2026-10-05_collation.php'");

            return $this->action_state($req, $res);
        }
    }

?>
