<?php

    namespace Tests\Authentication\PasswordHash;

    use InvalidArgumentException;
    use Tests\TestCase;
    use ZubZet\Framework\Authentication\PasswordHash\Password;

    class PasswordTest extends TestCase {

        private const ERROR_LENGTH = "Invalid password length.";
        private const ERROR_SALT = "A legacy or onion hash requires a salt.";

        // The credential of the seeded admin, password "password"
        private const LEGACY_PW = "password";
        private const LEGACY_SALT = "4401287036553e310907533.22322450";
        private const LEGACY_HASH = "772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615";

        // Known-answer vectors for "1205#demo": one produced by a WordPress 6.8+
        // install (wp_hash_password), one by the original phpass class ($P$, 2^8 rounds)
        private const WP_PW = "1205#demo";
        private const WP_BCRYPT = "\$wp\$2y\$10\$EvwwMyCdG7u1ShgRw5RUAOkL2TWi/XP12tEdLLuFRZNcJJe9k3GB2";
        private const WP_PORTABLE = "\$P\$BtYtOPOLRoHt8wylQ6fJWJEKhqfXiG1";

        // Cheaper than PHP's default, so a hash made with it has to be upgraded
        private const BELOW_COST = ["memory_cost" => 8192, "time_cost" => 1, "threads" => 1];

        // Password::hash()

        public function testProducesAnArgon2idHashThatVerifies(): void {
            $this->requireArgon2id();

            $hash = Password::hash("validpass");

            $this->assertStringStartsWith("\$argon2id\$", $hash);
            $this->assertTrue(password_verify("validpass", $hash));
        }

        public function testThrowsForAPasswordShorterThanTheMinimum(): void {
            $this->assertThrows(InvalidArgumentException::class, self::ERROR_LENGTH, fn() => Password::hash("ab"));
        }

        public function testThrowsForAPasswordLongerThanTheMaximum(): void {
            $this->assertThrows(InvalidArgumentException::class, self::ERROR_LENGTH, fn() => Password::hash(str_repeat("x", 1025)));
        }

        /** @dataProvider lengthBoundaries */
        public function testHashAppliesTheLengthBoundaries(string $password, bool $accepted): void {
            if(!$accepted) {
                $this->assertThrows(InvalidArgumentException::class, self::ERROR_LENGTH, fn() => Password::hash($password));
                return;
            }

            $this->requireArgon2id();
            $this->assertTrue(password_verify($password, Password::hash($password)));
        }

        public static function lengthBoundaries(): array {
            return [
                "an empty password" => ["", false],
                "two bytes" => ["ab", false],
                "three bytes" => ["abc", true],
                "1024 bytes" => [str_repeat("x", 1024), true],
                "1025 bytes" => [str_repeat("x", 1025), false],
                // The length is counted in bytes, not in characters
                "one character of two bytes" => ["ä", false],
                "two characters of three bytes" => ["äa", true],
                "512 characters of two bytes" => [str_repeat("ä", 512), true],
                "513 characters of two bytes" => [str_repeat("ä", 513), false],
            ];
        }

        // Password::verify() native

        public function testMatchesACurrentCostHashWithNoUpgrade(): void {
            $this->requireArgon2id();
            $stored = Password::hash("correct-horse");

            $result = Password::verify("correct-horse", $stored);

            $this->assertTrue($result->isCorrect());
            $this->assertFalse($result->isUpgradeNeeded());
        }

        public function testRejectsAWrongPassword(): void {
            $this->requireArgon2id();
            $stored = Password::hash("correct-horse");

            $result = Password::verify("wrong", $stored);

            $this->assertFalse($result->isCorrect());
            $this->assertFalse($result->isUpgradeNeeded());
        }

        public function testUpgradesABelowCostHashOnACorrectLoginRehashOnLogin(): void {
            $this->requireArgon2id();
            $stored = password_hash("correct-horse", PASSWORD_ARGON2ID, self::BELOW_COST);

            $result = Password::verify("correct-horse", $stored);

            $this->assertTrue($result->isCorrect());
            $this->assertTrue($result->isUpgradeNeeded());
        }

        public function testDoesNotUpgradeABelowCostHashOnAWrongPassword(): void {
            $this->requireArgon2id();
            $stored = password_hash("correct-horse", PASSWORD_ARGON2ID, self::BELOW_COST);

            $result = Password::verify("wrong", $stored);

            $this->assertFalse($result->isCorrect());
            $this->assertFalse($result->isUpgradeNeeded());
        }

        public function testUpgradesAHashOfAnotherAlgorithmOnACorrectLogin(): void {
            $this->requireArgon2id();
            $stored = password_hash("correct-horse", PASSWORD_BCRYPT, ["cost" => 4]);

            $result = Password::verify("correct-horse", $stored);

            $this->assertTrue($result->isCorrect());
            $this->assertTrue($result->isUpgradeNeeded());
        }

        public function testVerifiesNativelyWhenNoSchemeIsGiven(): void {
            $this->requireArgon2id();
            $stored = password_hash("correct-horse", PASSWORD_ARGON2ID, self::BELOW_COST);

            $this->assertTrue(Password::verify("correct-horse", $stored)->isCorrect());
            $this->assertTrue(Password::verify("correct-horse", $stored, Password::NATIVE)->isCorrect());
        }

        // Password::verify() guards

        public function testRejectsAnEmptyStoredValue(): void {
            $result = Password::verify("anything", "");

            $this->assertFalse($result->isCorrect());
            $this->assertFalse($result->isUpgradeNeeded());
        }

        /** @dataProvider schemes */
        public function testAnEmptyStoredValueIsWrongForEverySchemeAndNeedsNoSalt(string $scheme): void {
            // Legacy and onion would throw for the missing salt if the value was looked at
            $this->assertFalse(Password::verify("anything", "", $scheme)->isCorrect());
        }

        public static function schemes(): array {
            return [
                "native" => [Password::NATIVE],
                "legacy" => [Password::LEGACY],
                "onion" => [Password::ONION],
                "wordpress" => [Password::WORDPRESS],
            ];
        }

        public function testRejectsAPasswordBelowTheMinimumLength(): void {
            $this->requireArgon2id();
            // Its own hash, so only the length can make it wrong
            $stored = password_hash("ab", PASSWORD_ARGON2ID, self::BELOW_COST);

            $this->assertFalse(Password::verify("ab", $stored)->isCorrect());
        }

        public function testRejectsAPasswordAboveTheMaximumLength(): void {
            $this->requireArgon2id();
            $password = str_repeat("x", 1025);
            $stored = password_hash($password, PASSWORD_ARGON2ID, self::BELOW_COST);

            $this->assertFalse(Password::verify($password, $stored)->isCorrect());
        }

        /** @dataProvider lengthBoundaries */
        public function testVerifyAppliesTheLengthBoundaries(string $password, bool $accepted): void {
            $this->requireArgon2id();
            $stored = password_hash($password, PASSWORD_ARGON2ID, self::BELOW_COST);

            $this->assertSame($accepted, Password::verify($password, $stored)->isCorrect());
        }

        /** @dataProvider unknownSchemes */
        public function testRejectsAnUnknownScheme(?string $scheme): void {
            $this->requireArgon2id();
            // The hash matches the password, so only the scheme can make it wrong
            $stored = password_hash("correct-horse", PASSWORD_ARGON2ID, self::BELOW_COST);

            $result = Password::verify("correct-horse", $stored, $scheme);

            $this->assertFalse($result->isCorrect());
            $this->assertFalse($result->isUpgradeNeeded());
        }

        public static function unknownSchemes(): array {
            return [
                "bogus" => ["bogus"],
                "an explicit null" => [null],
                "an empty string" => [""],
                "a scheme in another case" => ["Native"],
            ];
        }

        // Password::verify() legacy

        public function testVerifiesALegacyHashAndYieldsANativeUpgrade(): void {
            $result = Password::verify(self::LEGACY_PW, self::LEGACY_HASH, Password::LEGACY, self::LEGACY_SALT);

            $this->assertTrue($result->isCorrect());
            $this->assertTrue($result->isUpgradeNeeded());
        }

        public function testRejectsAWrongPasswordAgainstALegacyHash(): void {
            $result = Password::verify("wrong", self::LEGACY_HASH, Password::LEGACY, self::LEGACY_SALT);

            $this->assertFalse($result->isCorrect());
            $this->assertFalse($result->isUpgradeNeeded());
        }

        public function testRejectsALegacyHashWithAnotherSalt(): void {
            $result = Password::verify(self::LEGACY_PW, self::LEGACY_HASH, Password::LEGACY, "another-salt");

            $this->assertFalse($result->isCorrect());
        }

        /** @dataProvider missingSalts */
        public function testThrowsWhenALegacyHashHasNoSalt(string $scheme, ?string $salt): void {
            $this->assertThrows(
                InvalidArgumentException::class,
                self::ERROR_SALT,
                fn() => Password::verify(self::LEGACY_PW, self::LEGACY_HASH, $scheme, $salt),
            );
        }

        public static function missingSalts(): array {
            return [
                "legacy with an empty salt" => [Password::LEGACY, ""],
                "legacy with a missing salt" => [Password::LEGACY, null],
                "onion with an empty salt" => [Password::ONION, ""],
                "onion with a missing salt" => [Password::ONION, null],
            ];
        }

        public function testRejectsANativeHashOnTheLegacyPath(): void {
            $this->requireArgon2id();
            $stored = password_hash(self::LEGACY_PW, PASSWORD_ARGON2ID, self::BELOW_COST);

            $result = Password::verify(self::LEGACY_PW, $stored, Password::LEGACY, self::LEGACY_SALT);

            $this->assertFalse($result->isCorrect());
        }

        // Password::verify() onion (+ Password::onionWrap)

        public function testWrapsALegacyHashVerifiesItAndYieldsANativeUpgrade(): void {
            $this->requireArgon2id();
            $onion = Password::onionWrap(self::LEGACY_HASH);

            $result = Password::verify(self::LEGACY_PW, $onion, Password::ONION, self::LEGACY_SALT);

            $this->assertStringStartsWith("\$argon2id\$", $onion);
            $this->assertTrue($result->isCorrect());
            $this->assertTrue($result->isUpgradeNeeded());
        }

        public function testRejectsAWrongPasswordAgainstAnOnionHash(): void {
            $this->requireArgon2id();
            $onion = Password::onionWrap(self::LEGACY_HASH);

            $result = Password::verify("wrong", $onion, Password::ONION, self::LEGACY_SALT);

            $this->assertFalse($result->isCorrect());
            $this->assertFalse($result->isUpgradeNeeded());
        }

        public function testWrapsWithAReducedCost(): void {
            $this->requireArgon2id();

            // An onion row is checked against 26 candidates, so it must stay cheap
            $options = password_get_info(Password::onionWrap(self::LEGACY_HASH))["options"];

            $this->assertSame(["memory_cost" => 12288, "time_cost" => 1, "threads" => 1], $options);
        }

        public function testALegacyAndAnOnionHashVerifyUnderEitherScheme(): void {
            $this->requireArgon2id();
            $onion = Password::onionWrap(self::LEGACY_HASH);

            $this->assertTrue(Password::verify(self::LEGACY_PW, $onion, Password::LEGACY, self::LEGACY_SALT)->isCorrect());
            $this->assertTrue(Password::verify(self::LEGACY_PW, self::LEGACY_HASH, Password::ONION, self::LEGACY_SALT)->isCorrect());
        }

        // Password::verify() wordpress

        public function testVerifiesAWordPress68WpBcryptHashAndYieldsANativeUpgrade(): void {
            $result = Password::verify(self::WP_PW, self::WP_BCRYPT, Password::WORDPRESS);

            $this->assertTrue($result->isCorrect());
            $this->assertTrue($result->isUpgradeNeeded());
            // WordPress trims before hashing, so surrounding whitespace must still match
            $this->assertTrue(Password::verify(" " . self::WP_PW . " ", self::WP_BCRYPT, Password::WORDPRESS)->isCorrect());
            $this->assertFalse(Password::verify("wrong", self::WP_BCRYPT, Password::WORDPRESS)->isCorrect());
        }

        public function testVerifiesAPre68PPhpassHashAndYieldsANativeUpgrade(): void {
            $result = Password::verify(self::WP_PW, self::WP_PORTABLE, Password::WORDPRESS);

            $this->assertTrue($result->isCorrect());
            $this->assertTrue($result->isUpgradeNeeded());
            $this->assertFalse(Password::verify("wrong", self::WP_PORTABLE, Password::WORDPRESS)->isCorrect());
        }

        /** @dataProvider wordPressVectors */
        public function testVerifiesEmojiUmlautsCjkSpecialCharacterSpacedAndLongPasswordsInBothFormats(string $password, string $bcrypt, string $portable): void {
            // The same password with its last byte flipped
            $flipped = substr($password, 0, -1) . chr(ord(substr($password, -1)) ^ 1);

            $this->assertSame(
                [
                    "bcrypt" => true,
                    "portable" => true,
                    "bcryptFlipped" => false,
                    "portableFlipped" => false,
                ],
                [
                    "bcrypt" => Password::verify($password, $bcrypt, Password::WORDPRESS)->isCorrect(),
                    "portable" => Password::verify($password, $portable, Password::WORDPRESS)->isCorrect(),
                    "bcryptFlipped" => Password::verify($flipped, $bcrypt, Password::WORDPRESS)->isCorrect(),
                    "portableFlipped" => Password::verify($flipped, $portable, Password::WORDPRESS)->isCorrect(),
                ],
            );
        }

        // [plaintext, $wp$ hash, $P$ hash] produced by WordPress 6.8's own
        // wp_hash_password() (bcrypt cost 12, PHP 8.4) and the original phpass
        // class, for inputs beyond ASCII: multi-byte, specials, spaces, >72 bytes
        public static function wordPressVectors(): array {
            return [
                "emoji" => ["pass🔐word🎉", "\$wp\$2y\$12\$LnjWc/WVYb50K.YRThpj1.QJK3aF35lpc2isL4N8NKy9/JFqYEs3q", "\$P\$Buu4e69hcG1Dh/YZ7LM6LQsZLFyh8Y1"],
                "umlauts" => ["Straße-Äöü", "\$wp\$2y\$12\$cBAuXoHl.bFlC4rSRmDHO.4fYLWQg13HBSGQ54vOHudt0KyQ5OCWK", "\$P\$Bq7QSWSjczMzjBRUjJVS/SpQGPjjGJ/"],
                "cjk" => ["密码パスワード", "\$wp\$2y\$12\$N35.zk83m.eaMO2uSi7u9uB5AQZfhoRNKOR2kbvFgDy.Ld3c6dZBK", "\$P\$BFek8YzuAxKlvIoTN695wx9kif1yOM/"],
                "specials" => ["!\"#\$%&'()*+,-./:;<=>?@[\\]^_`{|}~", "\$wp\$2y\$12\$sxG1S724OkUE/1EXzIHYMuazQA9W.b398xdjtMx/H0NSsGjt6hZEO", "\$P\$BC4nWNZk2tIEdGiwqb0aAFNu2o964t."],
                "innerSpaces" => ["pass word  two", "\$wp\$2y\$12\$B6xaz4.gez4ilfjFTsyuvuEumKR98vf23AwieK0LXdr9N0gaBIZ7.", "\$P\$BUJXwWkLwp/QlGSthsYZKVyPZ8Tzz80"],
                "longBytes" => ["Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#Zz9#", "\$wp\$2y\$12\$iaYVushWZrSBe2KUL1pFMuUEejhOLrs2dt8blLULrX3/cGtcFZjgu", "\$P\$BAWQ9eOBqyKZgJwAGBEkq0XVg4z1s01"],
            ];
        }

        public function testRejectsForeignAndMalformedStoredValues(): void {
            $this->requireArgon2id();
            $stored = [
                "unprefixedBcrypt" => substr(self::WP_BCRYPT, 3),
                "badRounds" => "\$P\$1" . substr(self::WP_PORTABLE, 4),
                "shortSalt" => substr(self::WP_PORTABLE, 0, 10),
                "native" => Password::hash(self::WP_PW),
            ];

            $this->assertSame(
                [
                    "unprefixedBcrypt" => false,
                    "badRounds" => false,
                    "shortSalt" => false,
                    "native" => false,
                ],
                array_map(fn($hash) => Password::verify(self::WP_PW, $hash, Password::WORDPRESS)->isCorrect(), $stored),
            );
        }
    }

?>
