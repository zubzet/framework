<?php

    // JSON probes for public framework APIs that the framework's own controllers
    // don't internally exercise. Used by tests/cypress/e2e/framework/api.cy.js.
    //
    // Each action runs ONE method and emits its result as JSON.

    class FrameworkApiController extends z_controller {

        // z_generalModel
        public function action_languageList(Request $req, Response $res): void {
            echo json_encode(model("z_general")->getLanguageList());
        }

        public function action_languageByValueKnown(Request $req, Response $res): void {
            echo json_encode([
                'EN' => model("z_general")->getLanguageByValue("EN"),
                'DE' => model("z_general")->getLanguageByValue("DE"),
            ]);
        }

        public function action_languageByValueUnknown(Request $req, Response $res): void {
            // Unknown value with custom default fallback
            echo json_encode([
                'unknownDefault'  => model("z_general")->getLanguageByValue("UNKNOWN"),
                'unknownExplicit' => model("z_general")->getLanguageByValue("UNKNOWN", 7),
            ]);
        }

        // z_userModel
        public function action_roleIdByName(Request $req, Response $res): void {
            echo json_encode([
                'fwapi_KnownRole' => model("z_user")->getRoleIdByRoleName("fwapi_KnownRole"),
                'no_such_role'    => model("z_user")->getRoleIdByRoleName("no_such_role"),
            ]);
        }

        public function action_changeRoleStateAdd(Request $req, Response $res): void {
            // user 700, role 700 — initially no link
            $userId = 700;
            $roleId = 250;

            $hasBefore = $this->hasRole($userId, $roleId);
            model("z_user")->changeRoleStateByUserIdAndRoleId($userId, $roleId, true);
            $hasAfter = $this->hasRole($userId, $roleId);

            // Idempotency: a second add should not duplicate the row.
            model("z_user")->changeRoleStateByUserIdAndRoleId($userId, $roleId, true);
            $countAfterDoubleAdd = $this->countRoleLinks($userId, $roleId);

            echo json_encode([
                'hasBefore'           => $hasBefore,
                'hasAfter'            => $hasAfter,
                'countAfterDoubleAdd' => $countAfterDoubleAdd,
            ]);
        }

        public function action_changeRoleStateRemove(Request $req, Response $res): void {
            $userId = 700;
            $roleId = 250;

            // Make sure the role is granted, then take it away.
            model("z_user")->changeRoleStateByUserIdAndRoleId($userId, $roleId, true);
            $hasBefore = $this->hasRole($userId, $roleId);
            model("z_user")->changeRoleStateByUserIdAndRoleId($userId, $roleId, false);
            $hasAfter = $this->hasRole($userId, $roleId);

            echo json_encode([
                'hasBefore' => $hasBefore,
                'hasAfter'  => $hasAfter,
            ]);
        }

        private function hasRole(int $userId, int $roleId): bool {
            $row = db()->exec(
                "SELECT COUNT(*) AS c FROM `z_user_role` WHERE `user`=? AND `role`=? AND `active`=1",
                "ii", $userId, $roleId
            )->resultToLine();
            return ((int)$row['c']) > 0;
        }

        private function countRoleLinks(int $userId, int $roleId): int {
            $row = db()->exec(
                "SELECT COUNT(*) AS c FROM `z_user_role` WHERE `user`=? AND `role`=? AND `active`=1",
                "ii", $userId, $roleId
            )->resultToLine();
            return (int)$row['c'];
        }
    }

?>
