<?php

    use OTPHP\TOTP;
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Authentication\Session;

    // Probe routes for tests/cypress/e2e/account/two-factor*.cy.js
    class TwoFactorProbeController extends z_controller {

        // GET /TwoFactorProbe/code/<userId>[?offset=<seconds>]
        public function action_code(Request $req, Response $res): void {
            $userId = (int) $req->getParameters(0, 1);
            $offset = (int) $req->getGet("offset", 0);

            $row = db()->exec(
                "SELECT `totp_secret` FROM `z_user` WHERE `id` = ?",
                "i",
                $userId
            )->resultToLine();

            $secret = $row["totp_secret"] ?? null;

            echo json_encode([
                "found" => !is_null($secret),
                "code" => is_null($secret) ? null : TOTP::create($secret)->at(time() + $offset),
            ]);
        }

        // GET /TwoFactorProbe/codeForSecret/<secret>
        public function action_codeForSecret(Request $req, Response $res): void {
            $secret = (string) $req->getParameters(0, 1);

            echo json_encode([
                "code" => TOTP::create($secret)->now(),
            ]);
        }

        // GET /TwoFactorProbe/user/<userId>
        public function action_user(Request $req, Response $res): void {
            $userId = (int) $req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT `totp_secret`, `totp_confirmed_at` FROM `z_user` WHERE `id` = ?",
                "i",
                $userId
            )->resultToLine();

            echo json_encode([
                "found" => !empty($row),
                "hasSecret" => !empty($row) && !is_null($row["totp_secret"]),
                "secret" => $row["totp_secret"] ?? null,
                "confirmedAt" => $row["totp_confirmed_at"] ?? null,
                "hasTwoFactor" => !empty($row) && !is_null($row["totp_confirmed_at"]),
            ]);
        }

        // GET /TwoFactorProbe/session/<token>
        public function action_session(Request $req, Response $res): void {
            $token = (string) $req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT `active`, `last_2fa`, `remaining_2fa_tries` FROM `z_logintoken` WHERE `token` = ?",
                "s",
                $token
            )->resultToLine();

            echo json_encode([
                "exists" => !empty($row),
                "active" => !empty($row) && 1 === (int) $row["active"],
                "lastTwoFactor" => $row["last_2fa"] ?? null,
                "remainingTries" => isset($row["remaining_2fa_tries"]) ? (int) $row["remaining_2fa_tries"] : null,
            ]);
        }

        // GET /TwoFactorProbe/ageSession/<token>[?seconds=<n>], php clock like recordTwoFactor()
        public function action_ageSession(Request $req, Response $res): void {
            $token = (string) $req->getParameters(0, 1);
            $seconds = $req->getGet("seconds", null);

            $stamp = is_null($seconds) ? null : date("Y-m-d H:i:s", time() - (int) $seconds);

            db()->exec(
                "UPDATE `z_logintoken` SET `last_2fa` = ? WHERE `token` = ?",
                "ss",
                $stamp,
                $token
            );

            echo json_encode(["lastTwoFactor" => $stamp]);
        }

        // GET /TwoFactorProbe/resetSession/<token>[?tries=<n>]
        public function action_resetSession(Request $req, Response $res): void {
            $token = (string) $req->getParameters(0, 1);
            $tries = (int) $req->getGet("tries", 5);

            db()->exec(
                "UPDATE `z_logintoken`
                 SET `active` = 1, `remaining_2fa_tries` = ?, `last_2fa` = NULL
                 WHERE `token` = ?",
                "is",
                $tries,
                $token
            );

            echo json_encode(["remainingTries" => $tries]);
        }

        // GET /TwoFactorProbe/setState/<userId>?secret=<base32>&confirmed=<0|1>, empty secret turns it off
        public function action_setState(Request $req, Response $res): void {
            $userId = (int) $req->getParameters(0, 1);
            $secret = (string) $req->getGet("secret", "");
            $confirmed = "1" === (string) $req->getGet("confirmed", "1");

            if(empty($secret)) {
                db()->exec(
                    "UPDATE `z_user` SET `totp_secret` = NULL, `totp_confirmed_at` = NULL WHERE `id` = ?",
                    "i",
                    $userId
                );

                echo json_encode(["hasTwoFactor" => false]);
                return;
            }

            db()->exec(
                "UPDATE `z_user`
                 SET `totp_secret` = ?, `totp_confirmed_at` = IF(?, CURRENT_TIMESTAMP(), NULL)
                 WHERE `id` = ?",
                "sii",
                $secret,
                (int) $confirmed,
                $userId
            );

            echo json_encode(["hasTwoFactor" => $confirmed]);
        }

        // GET /TwoFactorProbe/clearLoginTries/<userId>
        public function action_clearLoginTries(Request $req, Response $res): void {
            $userId = (int) $req->getParameters(0, 1);

            db()->exec("DELETE FROM `z_logintry` WHERE `userId` = ?", "i", $userId);

            echo json_encode(["cleared" => true]);
        }

        // GET /TwoFactorProbe/challenge/<userId>, the newest one
        public function action_challenge(Request $req, Response $res): void {
            $userId = (int) $req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT `token`, `active`, `expires_at` FROM `z_2fa_challenge`
                 WHERE `userId` = ? ORDER BY `id` DESC LIMIT 1",
                "i",
                $userId
            )->resultToLine();

            // Php clock, which wrote the column
            $expiresIn = empty($row["expires_at"]) ? null : strtotime($row["expires_at"]) - time();

            echo json_encode([
                "found" => !empty($row),
                "token" => $row["token"] ?? null,
                "active" => !empty($row) && 1 === (int) $row["active"],
                "expiresAt" => $row["expires_at"] ?? null,
                "expiresInSeconds" => $expiresIn,
            ]);
        }

        // GET /TwoFactorProbe/expireChallenge/<userId>, php clock like create2FAChallenge()
        public function action_expireChallenge(Request $req, Response $res): void {
            $userId = (int) $req->getParameters(0, 1);

            db()->exec(
                "UPDATE `z_2fa_challenge` SET `expires_at` = ? WHERE `userId` = ?",
                "si",
                date("Y-m-d H:i:s", time() - 60),
                $userId
            );

            echo json_encode(["expired" => true]);
        }

        // GET /TwoFactorProbe/challengeCount/<userId>
        public function action_challengeCount(Request $req, Response $res): void {
            $userId = (int) $req->getParameters(0, 1);

            $row = db()->exec(
                "SELECT COUNT(*) AS `total`, SUM(`active`) AS `active` FROM `z_2fa_challenge` WHERE `userId` = ?",
                "i",
                $userId
            )->resultToLine();

            echo json_encode([
                "total" => (int) $row["total"],
                "active" => (int) $row["active"],
            ]);
        }

        // POST /TwoFactorProbe/guardDefault, the configured window
        public function action_guardDefault(Request $req, Response $res) {
            if(!$req->requireFreshTwoFactor(boolResult: true)) {
                return $res->error("Two factor required", ["twoFactorRenew" => true]);
            }

            return $res->success();
        }

        // POST /TwoFactorProbe/guardCustom?seconds=<n>
        public function action_guardCustom(Request $req, Response $res) {
            $seconds = (int) $req->getGet("seconds", 60);

            if(!$req->requireFreshTwoFactor($seconds, true)) {
                return $res->error("Two factor required", ["twoFactorRenew" => true]);
            }

            return $res->success();
        }

        // GET /TwoFactorProbe/guardPage, the gate without boolResult
        public function action_guardPage(Request $req, Response $res) {
            $req->requireFreshTwoFactor();

            echo "guarded page reached";
        }

        // A guarded ZForm, gated before validateForm()
        public function action_formPage(Request $req, Response $res) {
            if($req->hasFormData()) {
                if(!$req->requireFreshTwoFactor(boolResult: true)) {
                    return $res->error("Two factor required", ["twoFactorRenew" => true]);
                }

                $formResult = $req->validateForm([
                    (new FormField("note"))->required()->length(3, 100),
                ]);

                if($formResult->hasErrors) return $res->formErrors($formResult->errors);

                return $res->success(["savedAt" => date("H:i:s")]);
            }

            return $res->render("two_factor_probe/form");
        }

        // The same gate behind a ZAction
        public function action_actionPage(Request $req, Response $res) {
            if($req->isAction("probe")) {
                if(!$req->requireFreshTwoFactor(boolResult: true)) {
                    return $res->error("Two factor required", ["twoFactorRenew" => true]);
                }

                return $res->success(["passedAt" => date("H:i:s")]);
            }

            return $res->render("two_factor_probe/action");
        }
    }

?>
