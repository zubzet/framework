<?php

    use ZubZet\Framework\Authentication\PasswordHash\Password;

    class PasswordHashProbeController extends z_controller {

        // Known-answer vector: the seeded admin's real legacy credential (password
        // "password", per zubzet/1_users.sql). Lets verify() be exercised for the
        // legacy/onion paths without LegacyHash's now-private internals.
        private const LEGACY_PW   = "password";
        private const LEGACY_SALT = "4401287036553e310907533.22322450";
        private const LEGACY_HASH = "772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615";

        // Known-answer vectors for "1205#demo": one produced by a WordPress 6.8+
        // install (wp_hash_password), one by the original phpass class ($P$, 2^8 rounds).
        private const WP_PW = "1205#demo";
        private const WP_BCRYPT = "\$wp\$2y\$10\$EvwwMyCdG7u1ShgRw5RUAOkL2TWi/XP12tEdLLuFRZNcJJe9k3GB2";
        private const WP_PORTABLE = "\$P\$BtYtOPOLRoHt8wylQ6fJWJEKhqfXiG1";

        // [plaintext, $wp$ hash, $P$ hash] produced by WordPress 6.8's own
        // wp_hash_password() (bcrypt cost 12, PHP 8.4) and the original phpass
        // class, for inputs beyond ASCII: multi-byte, specials, spaces, >72 bytes.
        private const WP_VECTORS = [
            "emoji" => ["pass🔐word🎉", "\$wp\$2y\$12\$LnjWc/WVYb50K.YRThpj1.QJK3aF35lpc2isL4N8NKy9/JFqYEs3q", "\$P\$Buu4e69hcG1Dh/YZ7LM6LQsZLFyh8Y1"],
            "umlauts" => ["Straße-Äöü", "\$wp\$2y\$12\$cBAuXoHl.bFlC4rSRmDHO.4fYLWQg13HBSGQ54vOHudt0KyQ5OCWK", "\$P\$Bq7QSWSjczMzjBRUjJVS/SpQGPjjGJ/"],
            "cjk" => ["密码パスワード", "\$wp\$2y\$12\$N35.zk83m.eaMO2uSi7u9uB5AQZfhoRNKOR2kbvFgDy.Ld3c6dZBK", "\$P\$BFek8YzuAxKlvIoTN695wx9kif1yOM/"],
            "specials" => ["!\"#\$%&'()*+,-./:;<=>?@[\\]^_`{|}~", "\$wp\$2y\$12\$sxG1S724OkUE/1EXzIHYMuazQA9W.b398xdjtMx/H0NSsGjt6hZEO", "\$P\$BC4nWNZk2tIEdGiwqb0aAFNu2o964t."],
            "innerSpaces" => ["pass word  two", "\$wp\$2y\$12\$B6xaz4.gez4ilfjFTsyuvuEumKR98vf23AwieK0LXdr9N0gaBIZ7.", "\$P\$BUJXwWkLwp/QlGSthsYZKVyPZ8Tzz80"],
            "longBytes" => ["Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#", "\$wp\$2y\$12\$iaYVushWZrSBe2KUL1pFMuUEejhOLrs2dt8blLULrX3/cGtcFZjgu", "\$P\$BAWQ9eOBqyKZgJwAGBEkq0XVg4z1s01"],
        ];

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

        public function action_verifyWordPressBcrypt(Request $req, Response $res) {
            $result = Password::verify(self::WP_PW, self::WP_BCRYPT, Password::WORDPRESS);
            return $res->json([
                "ok" => $result->isCorrect(),
                "needsUpgrade" => $result->isUpgradeNeeded(),
                // WordPress trims before hashing, so surrounding whitespace must still match.
                "okTrimmed" => Password::verify(" " . self::WP_PW . " ", self::WP_BCRYPT, Password::WORDPRESS)->isCorrect(),
                "wrong" => Password::verify("wrong", self::WP_BCRYPT, Password::WORDPRESS)->isCorrect(),
            ]);
        }

        public function action_verifyWordPressPortable(Request $req, Response $res) {
            $result = Password::verify(self::WP_PW, self::WP_PORTABLE, Password::WORDPRESS);
            return $res->json([
                "ok" => $result->isCorrect(),
                "needsUpgrade" => $result->isUpgradeNeeded(),
                "wrong" => Password::verify("wrong", self::WP_PORTABLE, Password::WORDPRESS)->isCorrect(),
            ]);
        }

        // Every vector must verify in both formats, and reject itself with one byte flipped.
        public function action_verifyWordPressVectors(Request $req, Response $res) {
            $out = [];
            foreach(self::WP_VECTORS as $name => [$pw, $bcrypt, $portable]) {
                $flipped = substr($pw, 0, -1) . chr(ord(substr($pw, -1)) ^ 1);
                $out[$name] = [
                    "bcrypt" => Password::verify($pw, $bcrypt, Password::WORDPRESS)->isCorrect(),
                    "portable" => Password::verify($pw, $portable, Password::WORDPRESS)->isCorrect(),
                    "bcryptFlipped" => Password::verify($flipped, $bcrypt, Password::WORDPRESS)->isCorrect(),
                    "portableFlipped" => Password::verify($flipped, $portable, Password::WORDPRESS)->isCorrect(),
                ];
            }
            return $res->json($out);
        }

        // Neither shim may accept a string outside its own format, nor a malformed one.
        public function action_verifyWordPressMalformed(Request $req, Response $res) {
            $stored = [
                "unprefixedBcrypt" => substr(self::WP_BCRYPT, 3),
                "badRounds" => "\$P\$1" . substr(self::WP_PORTABLE, 4),
                "shortSalt" => substr(self::WP_PORTABLE, 0, 10),
                "native" => Password::hash(self::WP_PW),
            ];
            return $res->json(array_map(
                fn($hash) => Password::verify(self::WP_PW, $hash, Password::WORDPRESS)->isCorrect(),
                $stored,
            ));
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
