<?php

    use ZubZet\Framework\Authentication\PasswordHash\Password;

    class PasswordHashProbeController extends z_controller {

        // Known-answer vector: the seeded admin's real legacy credential (password
        // "password", per zubzet/1_users.sql). Lets verify() be exercised for the
        // legacy/onion paths without LegacyHash's now-private internals.
        private const LEGACY_PW   = "password";
        private const LEGACY_SALT = "4401287036553e310907533.22322450";
        private const LEGACY_HASH = "772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615";

        public function action_hashValid(Request $req, Response $res) {
            $hash = Password::hash("validpass");
            return $res->json([
                "isArgon2id" => str_starts_with($hash, "\$argon2id\$"),
                "verifies" => password_verify("validpass", $hash),
            ]);
        }

        public function action_hashTooShort(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => Password::hash("ab"));
        }

        public function action_hashTooLong(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => Password::hash(str_repeat("x", 1025)));
        }

        public function action_verifyNativeMatch(Request $req, Response $res) {
            $stored = Password::hash("correct-horse");
            $result = Password::verify("correct-horse", $stored);
            return $res->json([
                "ok" => $result->isCorrect(),
                "needsUpgrade" => $result->isUpgradeNeeded(),
            ]);
        }

        public function action_verifyNativeMismatch(Request $req, Response $res) {
            $stored = Password::hash("correct-horse");
            return $res->json(["ok" => Password::verify("wrong", $stored)->isCorrect()]);
        }

        public function action_verifyNativeRehash(Request $req, Response $res) {
            // A below-cost native hash must self-heal on a correct login.
            $stored = password_hash("correct-horse", PASSWORD_ARGON2ID, [
                "memory_cost" => 8192,
                "time_cost" => 1,
                "threads" => 1,
            ]);
            $result = Password::verify("correct-horse", $stored);
            return $res->json([
                "ok" => $result->isCorrect(),
                "needsUpgrade" => $result->isUpgradeNeeded(),
            ]);
        }

        public function action_verifyEmptyStored(Request $req, Response $res) {
            return $res->json(["ok" => Password::verify("anything", "")->isCorrect()]);
        }

        public function action_verifyPasswordTooShort(Request $req, Response $res) {
            $stored = Password::hash("correct-horse");
            return $res->json(["ok" => Password::verify("ab", $stored)->isCorrect()]);
        }

        public function action_verifyPasswordTooLong(Request $req, Response $res) {
            $stored = Password::hash("correct-horse");
            return $res->json(["ok" => Password::verify(str_repeat("x", 1025), $stored)->isCorrect()]);
        }

        public function action_verifyUnknownScheme(Request $req, Response $res) {
            $stored = Password::hash("correct-horse");
            return $res->json(["ok" => Password::verify("correct-horse", $stored, "bogus")->isCorrect()]);
        }

        public function action_verifyLegacyMatch(Request $req, Response $res) {
            $result = Password::verify(
                self::LEGACY_PW,
                self::LEGACY_HASH,
                Password::LEGACY,
                self::LEGACY_SALT,
            );
            return $res->json([
                "ok" => $result->isCorrect(),
                "needsUpgrade" => $result->isUpgradeNeeded(),
            ]);
        }

        public function action_verifyLegacyMismatch(Request $req, Response $res) {
            return $res->json([
                "ok" => Password::verify(
                    "wrong",
                    self::LEGACY_HASH,
                    Password::LEGACY,
                    self::LEGACY_SALT,
                )->isCorrect(),
            ]);
        }

        public function action_verifyLegacyMissingSalt(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => Password::verify(
                self::LEGACY_PW,
                self::LEGACY_HASH,
                Password::LEGACY,
                "",
            ));
        }

        public function action_verifyOnionMatch(Request $req, Response $res) {
            $onion = Password::onionWrap(self::LEGACY_HASH);
            $result = Password::verify(
                self::LEGACY_PW,
                $onion,
                Password::ONION,
                self::LEGACY_SALT,
            );
            return $res->json([
                "ok" => $result->isCorrect(),
                "needsUpgrade" => $result->isUpgradeNeeded(),
                "wrappedFormat" => str_starts_with($onion, "\$argon2id\$"),
            ]);
        }

        public function action_verifyOnionMismatch(Request $req, Response $res) {
            $onion = Password::onionWrap(self::LEGACY_HASH);
            return $res->json([
                "ok" => Password::verify(
                    "wrong",
                    $onion,
                    Password::ONION,
                    self::LEGACY_SALT,
                )->isCorrect(),
            ]);
        }

        // Calling upgradePassword() with no pending upgrade is a misuse and must throw.
        public function action_upgradeWithoutPending(Request $req, Response $res) {
            $stored = Password::hash("correct-horse");
            $result = Password::verify("correct-horse", $stored);
            return $this->catchThrowableMessage(fn() => $result->upgradePassword());
        }

        // A pending upgrade yields a fresh native hash on demand.
        public function action_upgradePassword(Request $req, Response $res) {
            $stored = password_hash("correct-horse", PASSWORD_ARGON2ID, [
                "memory_cost" => 8192,
                "time_cost" => 1,
                "threads" => 1,
            ]);
            $result = Password::verify("correct-horse", $stored);
            $upgraded = $result->upgradePassword();
            return $res->json([
                "isArgon2id" => str_starts_with($upgraded, "\$argon2id\$"),
                "verifies" => password_verify("correct-horse", $upgraded),
                "rehashed" => !password_needs_rehash($upgraded, PASSWORD_ARGON2ID),
            ]);
        }

        // The happy-path probe keeps catchThrowableMessage's no-throw branch covered.
        public function action_catchHelperHappyPath(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => null);
        }

        private function catchThrowableMessage(\Closure $action): void {
            try {
                $action();
                response()->json(["threw" => false]);
            } catch (\Throwable $e) {
                response()->json([
                    "threw" => true,
                    "type" => get_class($e),
                    "message" => $e->getMessage(),
                ]);
            }
        }

    }

?>
