<?php

    namespace Tests\Authentication\PasswordHash;

    use LogicException;
    use Tests\TestCase;
    use ZubZet\Framework\Authentication\PasswordHash\Password;
    use ZubZet\Framework\Authentication\PasswordHash\Verification;

    class VerificationTest extends TestCase {

        private const ERROR_NO_UPGRADE = "No upgrade pending; guard with isUpgradeNeeded() before calling upgradePassword().";

        // Cheaper than PHP's default, so a hash made with it has to be upgraded
        private const BELOW_COST = ["memory_cost" => 8192, "time_cost" => 1, "threads" => 1];

        // Verification::upgradePassword()

        public function testProducesAFreshCurrentCostNativeHashWhenAnUpgradeIsPending(): void {
            $this->requireArgon2id();
            $stored = password_hash("correct-horse", PASSWORD_ARGON2ID, self::BELOW_COST);
            $result = Password::verify("correct-horse", $stored);

            $upgraded = $result->upgradePassword();

            $this->assertStringStartsWith("\$argon2id\$", $upgraded);
            $this->assertTrue(password_verify("correct-horse", $upgraded));
            $this->assertFalse(password_needs_rehash($upgraded, PASSWORD_ARGON2ID));
            $this->assertNotSame($stored, $upgraded);
        }

        public function testEveryUpgradeIsSaltedAnew(): void {
            $this->requireArgon2id();
            $result = Verification::createCorrectWithUpgrade("correct-horse");

            $this->assertNotSame($result->upgradePassword(), $result->upgradePassword());
        }

        public function testThrowsWhenCalledWithNoPendingUpgradeMisuse(): void {
            $this->requireArgon2id();
            $stored = Password::hash("correct-horse");
            $result = Password::verify("correct-horse", $stored);

            $this->assertThrows(LogicException::class, self::ERROR_NO_UPGRADE, fn() => $result->upgradePassword());
        }

        /** @dataProvider outcomes */
        public function testEachOutcomeReportsWhatItWasCreatedFor(Verification $result, bool $correct, bool $upgradeNeeded): void {
            $this->assertSame($correct, $result->isCorrect());
            $this->assertSame($upgradeNeeded, $result->isUpgradeNeeded());
        }

        public static function outcomes(): array {
            return [
                "a wrong password" => [Verification::createWrong(), false, false],
                "a correct password with a current hash" => [Verification::createCorrect(), true, false],
                "a correct password with a stale hash" => [Verification::createCorrectWithUpgrade("correct-horse"), true, true],
            ];
        }

        /** @dataProvider outcomesWithoutUpgrade */
        public function testOnlyAPendingUpgradeProducesAHash(Verification $result): void {
            $this->assertThrows(LogicException::class, self::ERROR_NO_UPGRADE, fn() => $result->upgradePassword());
        }

        public static function outcomesWithoutUpgrade(): array {
            return [
                "a wrong password" => [Verification::createWrong()],
                "a correct password with a current hash" => [Verification::createCorrect()],
            ];
        }
    }

?>
