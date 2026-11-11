<?php

    // Exercises src/Database/Interaction.php directly through db().
    // Tables z_test_grouping (3 rows) and z_test_empty (0 rows) are
    // created in migrations/2026-05-12_DatabaseProbe.sql; cypress spec
    // is tests/cypress/e2e/database/interaction.cy.js.
    class DatabaseProbeController extends z_controller {

        // countTableEntries delegates to getFullTable() with COUNT(*).
        public function action_countTableEntriesHappy(Request $req, Response $res) {
            return $res->json([
                "count" => (int) db()->countTableEntries("z_test_grouping"),
            ]);
        }

        public function action_countTableEntriesEmpty(Request $req, Response $res) {
            return $res->json([
                "count" => (int) db()->countTableEntries("z_test_empty"),
            ]);
        }

    }

?>
