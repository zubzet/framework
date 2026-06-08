<?php

    // Probe routes used by tests/cypress/e2e/account/auth-flows.cy.js to read
    // authentication-related rows directly from the database when the natural
    // delivery path is email (which we can also assert via mailhog, but pulling
    // the row is simpler and more reliable for tests that need the token).
    //
    // Each action emits a single fixed-shape JSON object - fields are null
    // (and `found`/`exists` is false) when the row isn't there, so callers
    // can rely on the shape without the action branching on emptiness.

    class AuthProbeController extends z_controller {

        /**
         * Latest active reset code for a user.
         * GET /auth-probe/lastResetCode/<userId>
         */
        public function action_lastResetCode(Request $req, Response $res): void {
            $userId = (int)$req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT `id`, `userId`, `refId`, `reason`, `active`, `created`
                 FROM `z_password_reset`
                 WHERE `userId` = ? AND `active` = 1
                 ORDER BY `id` DESC LIMIT 1",
                "i",
                $userId
            )->resultToLine();

            echo json_encode([
                'found'  => !empty($row),
                'id'     => isset($row['id']) ? (int)$row['id'] : null,
                'userId' => isset($row['userId']) ? (int)$row['userId'] : null,
                'code'   => $row['refId'] ?? null,
                'reason' => $row['reason'] ?? null,
                'active' => !empty($row) && (int)$row['active'] === 1,
            ]);
        }

        /**
         * Whether a login token is still active in z_logintoken.
         * GET /auth-probe/tokenActive/<token>
         */
        public function action_tokenActive(Request $req, Response $res): void {
            $token = $req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT `active` FROM `z_logintoken` WHERE `token` = ?",
                "s",
                $token
            )->resultToLine();

            echo json_encode([
                'exists' => !empty($row),
                'active' => !empty($row) && (int)$row['active'] === 1,
            ]);
        }

        /**
         * Reads the user's verified timestamp from the DB.
         * GET /auth-probe/userVerified/<userId>
         */
        public function action_userVerified(Request $req, Response $res): void {
            $userId = (int)$req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT `id`, `verified` FROM `z_user` WHERE `id` = ?",
                "i",
                $userId
            )->resultToLine();

            echo json_encode([
                'found'    => !empty($row),
                'id'       => isset($row['id']) ? (int)$row['id'] : null,
                'verified' => $row['verified'] ?? null,
            ]);
        }

        /**
         * Verify a password against the stored hash for a user (used by reset test).
         * GET /auth-probe/checkPassword/<userId>?password=<plain>
         */
        public function action_checkPassword(Request $req, Response $res): void {
            $userId   = (int)$req->getParameters(0, 1);
            $password = $req->getGet("password", "");

            $row = db()->exec(
                "SELECT `password`, `salt`, `password_scheme` FROM `z_user` WHERE `id` = ?",
                "i",
                $userId
            )->resultToLine();

            $ok = !empty($row) && (bool)$req->getModel("z_login")->checkPassword(
                $password, $row['password'], $row['salt'], $row['password_scheme']
            );

            echo json_encode([
                'found' => !empty($row),
                'ok'    => $ok,
            ]);
        }

        /**
         * The stored password_scheme for a user (legacy|onion|native|null).
         * GET /auth-probe/scheme/<userId>
         */
        public function action_scheme(Request $req, Response $res): void {
            $userId = (int)$req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT `password_scheme` FROM `z_user` WHERE `id` = ?",
                "i",
                $userId
            )->resultToLine();

            echo json_encode([
                'found'  => !empty($row),
                'scheme' => $row['password_scheme'] ?? null,
            ]);
        }
    }

?>
