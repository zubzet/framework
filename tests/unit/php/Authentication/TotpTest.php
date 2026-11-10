<?php

    namespace Tests\Authentication;

    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;
    use ZubZet\Framework\Authentication\Totp;

    class TotpTest extends TestCase {

        // RFC 6238 appendix B: the ASCII secret "12345678901234567890" in Base32
        private const RFC_SECRET = "GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ";

        // The last second of its period, so the neighbouring periods reach from
        // 59 seconds before it to 30 seconds after it
        private const NOW = 1111111109;
        private const CODE_NOW = "081804";

        private const ERROR_INVALID_SECRET = "The two factor secret is not valid Base32.";

        // Generation

        /** @dataProvider rfc6238Vectors */
        public function testMatchesTheRfc6238VectorFor(int $timestamp, string $expected): void {
            $this->assertSame($expected, $this->getTotp()->at($timestamp));
        }

        public static function rfc6238Vectors(): array {
            // The RFC lists eight digits for SHA1, a six digit code is their last six
            return [
                "59" => [59, "287082"],
                "1111111109" => [1111111109, "081804"],
                "1111111111" => [1111111111, "050471"],
                "1234567890" => [1234567890, "005924"],
                "2000000000" => [2000000000, "279037"],
                "20000000000" => [20000000000, "353130"],
            ];
        }

        public function testKeepsTheCodeForAWholePeriodAndChangesItWithTheNext(): void {
            $totp = $this->getTotp();

            $this->assertSame($totp->at(30), $totp->at(59));
            $this->assertNotSame($totp->at(30), $totp->at(60));
        }

        public function testReadsASecretRegardlessOfCaseAndPadding(): void {
            $totp = $this->getTotp(strtolower(self::RFC_SECRET) . "====");

            $this->assertSame("287082", $totp->at(59));
        }

        public function testGetSecretReturnsSecretAsGiven(): void {
            $secret = strtolower(self::RFC_SECRET) . "====";

            $this->assertSame($secret, $this->getTotp($secret)->getSecret());
        }

        public function testEmptySecretThrowsException(): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(self::ERROR_INVALID_SECRET);

            $this->getTotp("")->at(self::NOW);
        }

        public function testSecretOfOnlyPaddingThrowsException(): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(self::ERROR_INVALID_SECRET);

            $this->getTotp("====")->at(self::NOW);
        }

        public function testSecretTooShortForAByteThrowsException(): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(self::ERROR_INVALID_SECRET);

            $this->getTotp("A")->at(self::NOW);
        }

        public function testSecretWithCharacterOutsideAlphabetThrowsException(): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(self::ERROR_INVALID_SECRET);

            $this->getTotp("GEZDGNBV1")->at(self::NOW);
        }

        public function testSecretWithWhitespaceThrowsException(): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(self::ERROR_INVALID_SECRET);

            $this->getTotp("GEZD GNBV")->at(self::NOW);
        }

        public function testSecretWithPaddingInTheMiddleThrowsException(): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(self::ERROR_INVALID_SECRET);

            $this->getTotp("GEZD=GNBV")->at(self::NOW);
        }

        public function testGenerateSecretReturns32Base32Characters(): void {
            $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', Totp::generateSecret());
        }

        public function testGenerateSecretReturnsDifferentSecretEveryTime(): void {
            $this->assertNotSame(Totp::generateSecret(), Totp::generateSecret());
        }

        /**
         * @depends testGenerateSecretReturns32Base32Characters
         */
        public function testGeneratedSecretYieldsCodesThatVerify(): void {
            $totp = $this->getTotp(Totp::generateSecret());

            $this->assertMatchesRegularExpression('/^\d{6}$/', $totp->at(self::NOW));
            $this->assertTrue($totp->verify($totp->at(self::NOW), 0, self::NOW));
        }

        public function testNowVerifiesWithoutTimestamp(): void {
            $totp = $this->getTotp();

            // The default drift covers a period change between the two calls
            $this->assertTrue($totp->verify($totp->now()));
        }

        // Verification

        public function testAcceptsTheCodeOfTheCurrentPeriod(): void {
            $this->assertTrue($this->getTotp()->verify(self::CODE_NOW, 1, self::NOW));
        }

        public function testAcceptsTheCodeOfThePeriodBeforeAndAfter(): void {
            $totp = $this->getTotp();

            $this->assertTrue($totp->verify(self::CODE_NOW, 1, self::NOW + 30));
            $this->assertTrue($totp->verify(self::CODE_NOW, 1, self::NOW - 59));
        }

        public function testRejectsACodeTwoPeriodsAway(): void {
            $totp = $this->getTotp();

            $this->assertFalse($totp->verify(self::CODE_NOW, 1, self::NOW + 31));
            $this->assertFalse($totp->verify(self::CODE_NOW, 1, self::NOW - 60));
        }

        public function testVerifyToleratesOnePeriodByDefault(): void {
            $totp = $this->getTotp();

            $this->assertTrue($totp->verify(self::CODE_NOW, timestamp: self::NOW + 30));
            $this->assertFalse($totp->verify(self::CODE_NOW, timestamp: self::NOW + 31));
        }

        public function testVerifyWithoutDriftAcceptsOnlyCurrentPeriod(): void {
            $totp = $this->getTotp();

            $this->assertTrue($totp->verify(self::CODE_NOW, 0, self::NOW));
            $this->assertTrue($totp->verify(self::CODE_NOW, 0, self::NOW - 29));

            $this->assertFalse($totp->verify(self::CODE_NOW, 0, self::NOW + 1));
            $this->assertFalse($totp->verify(self::CODE_NOW, 0, self::NOW - 30));
        }

        public function testVerifyWithWiderDriftAcceptsPeriodsFurtherAway(): void {
            $totp = $this->getTotp();

            $this->assertTrue($totp->verify(self::CODE_NOW, 2, self::NOW + 60));
            $this->assertFalse($totp->verify(self::CODE_NOW, 2, self::NOW + 61));
        }

        /** @dataProvider wrongCodes */
        public function testRejectsAWrongCodeAndACodeOfTheWrongLength(string $code): void {
            $this->assertFalse($this->getTotp()->verify($code, 1, self::NOW));
        }

        public static function wrongCodes(): array {
            return [
                "a wrong code" => ["081805"],
                "five digits" => ["81804"],
                "seven digits" => ["0081804"],
                "an empty code" => [""],
                "a leading space" => [" 081804"],
                "a trailing newline" => ["081804\n"],
            ];
        }

        // Provisioning uri

        public function testCarriesIssuerLabelAndSecretInTheFormAuthenticatorsRead(): void {
            $totp = Totp::create(self::RFC_SECRET, "user+a@example.com", "QA Suite & Co");

            $this->assertSame(
                "otpauth://totp/QA%20Suite%20%26%20Co%3Auser%2Ba%40example.com"
                    . "?issuer=QA%20Suite%20%26%20Co&secret=" . self::RFC_SECRET,
                $totp->getProvisioningUri()
            );
        }

        public function testDropsAColonFromTheLabelAndTheIssuerWhichSeparatesTheTwo(): void {
            $totp = Totp::create(self::RFC_SECRET, "a:b@example.com", "QA: Suite");

            $this->assertSame(
                "otpauth://totp/QA%20Suite%3Aab%40example.com?issuer=QA%20Suite&secret=" . self::RFC_SECRET,
                $totp->getProvisioningUri()
            );
        }

        public function testProvisioningUriWithoutIssuerNamesOnlyLabel(): void {
            $totp = new Totp(self::RFC_SECRET, "user@example.com");

            $this->assertSame(
                "otpauth://totp/user%40example.com?secret=" . self::RFC_SECRET,
                $totp->getProvisioningUri()
            );
        }

        public function testCreateBuildsSameInstanceAsConstructor(): void {
            $this->assertEquals(
                new Totp(self::RFC_SECRET, "user@example.com", "QA Suite"),
                Totp::create(self::RFC_SECRET, "user@example.com", "QA Suite")
            );
        }

        protected function getTotp(string $secret = self::RFC_SECRET): Totp {
            return Totp::create($secret);
        }
    }
?>
