<?php

    use ZubZet\Framework\Authentication\PasswordHash\Password;

    /**
     * Guards the framework's password API against breaking changes for consuming
     * apps. They call model("z_login")->checkPassword($pw, $hash, $salt) with the
     * 3-argument shape (no scheme) on their own user tables, and rely on legacy
     * hashes still verifying. See jouri's Authentication/UserController.
     *
     * If a refactor breaks the 3-arg signature or stops inferring the scheme from
     * the salt, these probes flip and the spec fails.
     */
    class ApiCompatProbeController extends z_controller {

        // The seeded admin's real legacy credential (zubzet/1_users.sql).
        private const LEGACY_PW   = "password";
        private const LEGACY_SALT = "4401287036553e310907533.22322450";
        private const LEGACY_HASH = "772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615";

        /** Consumer call: checkPassword($pw, $legacyHash, $salt) — no scheme. */
        public function action_legacy3Arg(Request $req, Response $res) {
            $login = $req->getModel("z_login");
            return $res->json([
                "correct" => $login->checkPassword(self::LEGACY_PW, self::LEGACY_HASH, self::LEGACY_SALT),
                "wrong" => $login->checkPassword("nope", self::LEGACY_HASH, self::LEGACY_SALT),
            ]);
        }

        /** Consumer call: checkPassword($pw, $nativeHash, null) — no scheme, no salt. */
        public function action_native3Arg(Request $req, Response $res) {
            $login = $req->getModel("z_login");
            $hash = Password::hash("fresh-secret");
            return $res->json([
                "correct" => $login->checkPassword("fresh-secret", $hash, null),
                "wrong" => $login->checkPassword("nope", $hash, null),
            ]);
        }
    }
?>
