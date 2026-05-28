<?php

    // Exercises src/Database/Interaction.php directly through db().
    // Tables z_test_grouping (3 rows) and z_test_empty (0 rows) are
    // created in migrations/2026-05-12_DatabaseProbe.sql; cypress spec
    // is tests/cypress/e2e/database/interaction.cy.js.
    class DatabaseProbeController extends z_controller {

        // mergeAsGroup($groupBy) - returns each row's full assoc keyed
        // and grouped by the group_id column. Two-group dataset proves
        // both the "new key" and "append-to-existing" branches.
        public function action_mergeAsGroupBasic(Request $req, Response $res) {
            $groups = db()->exec("SELECT * FROM z_test_grouping ORDER BY val")
                          ->mergeAsGroup("group_id");
            return $res->json($groups);
        }

        // mergeAsGroup($groupBy, $subElement) - same dataset but only
        // the `val` column is collected per group. Exercises the
        // isset($subElement) branch + the continue.
        public function action_mergeAsGroupSubElement(Request $req, Response $res) {
            $groups = db()->exec("SELECT * FROM z_test_grouping ORDER BY val")
                          ->mergeAsGroup("group_id", "val");
            return $res->json($groups);
        }

        // Empty result set -> the foreach body never runs; returns [].
        public function action_mergeAsGroupEmpty(Request $req, Response $res) {
            $groups = db()->exec("SELECT * FROM z_test_grouping WHERE group_id = ?", "i", 99999)
                          ->mergeAsGroup("group_id");
            return $res->json($groups);
        }

        // Single-group filter: only the "new key" branch runs, never
        // the "existing key" branch. Pins the contract for that shape.
        public function action_mergeAsGroupSingleGroup(Request $req, Response $res) {
            $groups = db()->exec("SELECT * FROM z_test_grouping WHERE group_id = ?", "i", 2)
                          ->mergeAsGroup("group_id");
            return $res->json($groups);
        }

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
