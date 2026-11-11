<?php

    namespace Tests\IncludedComponents\models;

    use InvalidArgumentException;
    use Tests\TestCase;
    use ZubZet\Framework\Authentication\PasswordHash\Password;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\ZubZet;
    use z_loginModel;

    require_once FRAMEWORK_ROOT . "/src/IncludedComponents/models/z_loginModel.php";

    class z_loginModelTest extends TestCase {

        // The credential of the seeded admin, password "password"
        private const LEGACY_PW = "password";
        private const LEGACY_SALT = "4401287036553e310907533.22322450";
        private const LEGACY_HASH = "772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615";

        // Cheaper than PHP's default, the cost does not matter for the scheme
        private const FAST_COST = ["memory_cost" => 8192, "time_cost" => 1, "threads" => 1];

        // Consumer API: the three argument checkPassword()

        public function testVerifiesALegacyHashVia3ArgCheckPasswordExistingUsers(): void {
            $login = $this->login();

            $this->assertTrue($login->checkPassword(self::LEGACY_PW, self::LEGACY_HASH, self::LEGACY_SALT));
            $this->assertFalse($login->checkPassword("nope", self::LEGACY_HASH, self::LEGACY_SALT));
        }

        public function testVerifiesANativeHashVia3ArgCheckPasswordNewUsers(): void {
            $this->requireArgon2id();
            $login = $this->login();
            $hash = Password::hash("fresh-secret");

            $this->assertTrue($login->checkPassword("fresh-secret", $hash, null));
            $this->assertFalse($login->checkPassword("nope", $hash, null));
        }

        public function testAnEmptySaltInfersTheNativeScheme(): void {
            $this->requireArgon2id();
            $hash = password_hash("fresh-secret", PASSWORD_ARGON2ID, self::FAST_COST);

            $this->assertTrue($this->login()->checkPassword("fresh-secret", $hash, ""));
        }

        public function testASaltInfersTheLegacySchemeSoANativeHashDoesNotVerify(): void {
            $this->requireArgon2id();
            $hash = password_hash("fresh-secret", PASSWORD_ARGON2ID, self::FAST_COST);

            $this->assertFalse($this->login()->checkPassword("fresh-secret", $hash, self::LEGACY_SALT));
        }

        public function testAnExplicitSchemeWinsOverTheSaltInference(): void {
            $this->requireArgon2id();
            $login = $this->login();
            $hash = password_hash("fresh-secret", PASSWORD_ARGON2ID, self::FAST_COST);

            // The salt alone would pick the legacy scheme, and would fail here
            $this->assertTrue($login->checkPassword("fresh-secret", $hash, self::LEGACY_SALT, Password::NATIVE));
            $this->assertTrue($login->checkPassword(self::LEGACY_PW, self::LEGACY_HASH, self::LEGACY_SALT, Password::LEGACY));
        }

        public function testAnExplicitLegacySchemeWithoutASaltThrows(): void {
            // The salt is empty, so inference alone would have picked the native scheme
            $this->assertThrows(
                InvalidArgumentException::class,
                "A legacy or onion hash requires a salt.",
                fn() => $this->login()->checkPassword(self::LEGACY_PW, self::LEGACY_HASH, null, Password::LEGACY),
            );
        }

        public function testAPasswordAboveTheMaximumLengthIsWrongAndDoesNotThrow(): void {
            $this->assertFalse($this->login()->checkPassword(str_repeat("x", 1025), self::LEGACY_HASH, self::LEGACY_SALT));
        }

        // The model takes its collaborators in the constructor and uses neither here
        private function login(): z_loginModel {
            return new z_loginModel($this->createMock(Connection::class), $this->createMock(ZubZet::class));
        }
    }

?>
