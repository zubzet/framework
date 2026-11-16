<?php

    namespace Tests\Security\Secrets;

    use RuntimeException;
    use Tests\TestCase;
    use ZubZet\Framework\Security\Secrets\DecryptionException;
    use ZubZet\Framework\Security\Secrets\Encryption;

    class EncryptionTest extends TestCase {

        private const KEY = "unit-test-encryption-key-0123456789abcdef";
        private const SECRET = "s3-secret-key/with+special=chars äöü";
        private const HEADER = "zenc:aes-256-gcm:";

        private const ERROR_KEY_LENGTH = "The setting 'encryption_key' must be at least 32 bytes long.";
        private const ERROR_UNKNOWN_FORMAT = "The value is not in a known encryption format.";
        private const ERROR_MALFORMED = "The encrypted value is malformed.";
        private const ERROR_NOT_DECRYPTED = "The value could not be decrypted, the key is wrong or the value was modified.";

        protected function setUp(): void {
            $this->bootFramework([Encryption::SETTING => self::KEY]);
        }

        // Round trip

        public function testEncryptsToAUrlSafeValueNamingItsCipherThatDecryptsToThePlaintext(): void {
            $encrypted = encryptSecret(self::SECRET);

            $this->assertMatchesRegularExpression('/^zenc:aes-256-gcm:[A-Za-z0-9_-]+$/', $encrypted);
            $this->assertStringNotContainsString(self::SECRET, $encrypted);
            $this->assertSame(self::SECRET, decryptSecret($encrypted));
        }

        public function testTheGlobalHelpersAndTheClassReadEachOthersValues(): void {
            $this->assertSame(self::SECRET, Encryption::decryptSecret(encryptSecret(self::SECRET)));
            $this->assertSame(self::SECRET, decryptSecret(Encryption::encryptSecret(self::SECRET)));
        }

        public function testRoundTripsAnEmptyString(): void {
            $this->assertSame("", Encryption::decryptSecret(Encryption::encryptSecret("")));
        }

        public function testRoundTripsEveryByteValueIncludingNullBytesAndInvalidUtf8(): void {
            $bytes = implode("", array_map("chr", range(0, 255)));

            $this->assertSame($bytes, decryptSecret(encryptSecret($bytes)));
        }

        public function testRoundTripsAJsonEncodedArray(): void {
            $data = [
                "accessKey" => "AKIAEXAMPLE",
                "secretKey" => "quote\" backslash\\ newline\n slash/ plus+ equals= äöü 🔑",
                "applicationId" => 42,
                "options" => ["region" => null, "pathStyle" => true],
            ];

            $decoded = json_decode(decryptSecret(encryptSecret(json_encode($data))), true);

            $this->assertSame($data, $decoded);
        }

        public function testRoundTripsAValueOfEveryLengthUpToThreeBlocks(): void {
            // The payload length decides how many bits the last base64url
            // character carries, so every remainder has to survive
            for($length = 0; $length <= 48; $length++) {
                $plaintext = str_repeat("x", $length);
                $this->assertSame($plaintext, decryptSecret(encryptSecret($plaintext)));
            }
        }

        public function testUsesAFreshIvForEveryEncryption(): void {
            $this->assertNotSame(encryptSecret(self::SECRET), encryptSecret(self::SECRET));
        }

        // Decryption failures

        public function testThrowsADecryptionExceptionForAWrongKey(): void {
            $encrypted = encryptSecret(self::SECRET);
            $this->setSettings([Encryption::SETTING => str_repeat("w", 32)]);

            $this->assertDecryptionFails(self::ERROR_NOT_DECRYPTED, fn() => decryptSecret($encrypted));
        }

        public function testThrowsADecryptionExceptionForAModifiedValue(): void {
            $payload = $this->decodePayload(encryptSecret(self::SECRET));
            $last = strlen($payload) - 1;
            $payload[$last] = chr(ord($payload[$last]) ^ 1);

            $this->assertDecryptionFails(self::ERROR_NOT_DECRYPTED, fn() => decryptSecret(self::HEADER . $this->encodePayload($payload)));
        }

        public function testThrowsADecryptionExceptionForAModifiedIv(): void {
            $payload = $this->decodePayload(encryptSecret(self::SECRET));
            $payload[0] = chr(ord($payload[0]) ^ 1);

            $this->assertDecryptionFails(self::ERROR_NOT_DECRYPTED, fn() => decryptSecret(self::HEADER . $this->encodePayload($payload)));
        }

        public function testThrowsADecryptionExceptionForAModifiedTag(): void {
            // The tag follows the 12 bytes of the iv
            $payload = $this->decodePayload(encryptSecret(self::SECRET));
            $payload[12] = chr(ord($payload[12]) ^ 1);

            $this->assertDecryptionFails(self::ERROR_NOT_DECRYPTED, fn() => decryptSecret(self::HEADER . $this->encodePayload($payload)));
        }

        /** @dataProvider unknownFormats */
        public function testThrowsADecryptionExceptionForAnUnknownFormat(string $value): void {
            $this->assertDecryptionFails(self::ERROR_UNKNOWN_FORMAT, fn() => decryptSecret($value));
        }

        public static function unknownFormats(): array {
            return [
                "a plaintext" => ["plaintext"],
                "an empty string" => [""],
                "a value without a payload part" => ["zenc:aes-256-gcm"],
                "another marker" => ["xenc:aes-256-gcm:c2hvcnQ"],
                "a marker in another case" => ["ZENC:aes-256-gcm:c2hvcnQ"],
            ];
        }

        public function testThrowsADecryptionExceptionForAnUnknownCipher(): void {
            $relabeled = str_replace(self::HEADER, "zenc:rot13:", encryptSecret(self::SECRET));

            $this->assertDecryptionFails("The value was encrypted with the unknown cipher 'rot13'.", fn() => decryptSecret($relabeled));
        }

        public function testACipherNameIsCaseSensitive(): void {
            $relabeled = str_replace(self::HEADER, "zenc:AES-256-GCM:", encryptSecret(self::SECRET));

            $this->assertDecryptionFails("The value was encrypted with the unknown cipher 'AES-256-GCM'.", fn() => decryptSecret($relabeled));
        }

        /** @dataProvider malformedPayloads */
        public function testThrowsADecryptionExceptionForAMalformedPayload(string $payload): void {
            $this->assertDecryptionFails(self::ERROR_MALFORMED, fn() => decryptSecret(self::HEADER . $payload));
        }

        public static function malformedPayloads(): array {
            return [
                "a truncated payload" => ["c2hvcnQ"],
                "an empty payload" => [""],
                // iv and tag take 28 bytes, 27 bytes are 36 characters
                "a payload one byte short of iv and tag" => [str_repeat("A", 36)],
                "characters outside base64url" => ["ab+/"],
                "an impossible base64 length" => ["abcde"],
                "base64 padding" => ["c2hvcnQ="],
            ];
        }

        public function testThrowsADecryptionExceptionForANonCanonicalEncoding(): void {
            // 29 payload bytes leave unused bits in the last character, so
            // flipping its lowest bit still decodes to the same bytes
            $encrypted = encryptSecret("x");
            $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
            $modified = substr($encrypted, 0, -1) . $alphabet[strpos($alphabet, substr($encrypted, -1)) ^ 1];

            $this->assertSame(
                $this->decodePayload($encrypted),
                $this->decodePayload($modified),
                "Both spellings have to stand for the same bytes, or this is the modified value test again",
            );

            $this->assertDecryptionFails(self::ERROR_MALFORMED, fn() => decryptSecret($modified));
        }

        public function testADecryptionExceptionIsARuntimeException(): void {
            // Callers that catch the failure of a missing key catch this one too
            $this->assertInstanceOf(RuntimeException::class, new DecryptionException("message"));
        }

        // Key setting

        public function testThrowsARuntimeExceptionForAShortKeyOnEncryption(): void {
            $this->setSettings([Encryption::SETTING => "too-short"]);

            $this->assertKeyIsRejected(fn() => encryptSecret(self::SECRET));
        }

        public function testThrowsARuntimeExceptionForAMissingKeyOnEncryption(): void {
            $this->removeSetting(Encryption::SETTING);

            $this->assertKeyIsRejected(fn() => encryptSecret(self::SECRET));
        }

        public function testThrowsARuntimeExceptionForAMissingKeyOnDecryption(): void {
            $encrypted = encryptSecret(self::SECRET);
            $this->removeSetting(Encryption::SETTING);

            $this->assertKeyIsRejected(fn() => decryptSecret($encrypted));
        }

        public function testThrowsARuntimeExceptionForAnEmptyKey(): void {
            $this->setSettings([Encryption::SETTING => ""]);

            $this->assertKeyIsRejected(fn() => encryptSecret(self::SECRET));
        }

        public function testAKeyOfExactlyTheMinimumLengthIsAccepted(): void {
            $this->setSettings([Encryption::SETTING => str_repeat("k", Encryption::MIN_KEY_LENGTH)]);

            $this->assertSame(self::SECRET, decryptSecret(encryptSecret(self::SECRET)));
        }

        public function testAKeyOneByteBelowTheMinimumLengthIsRejected(): void {
            $this->setSettings([Encryption::SETTING => str_repeat("k", Encryption::MIN_KEY_LENGTH - 1)]);

            $this->assertKeyIsRejected(fn() => encryptSecret(self::SECRET));
        }

        public function testTheKeyLengthIsCountedInBytes(): void {
            // 16 characters of two bytes each
            $this->setSettings([Encryption::SETTING => str_repeat("ä", 16)]);

            $this->assertSame(self::SECRET, decryptSecret(encryptSecret(self::SECRET)));
        }

        private function assertDecryptionFails(string $message, callable $action): void {
            $this->assertThrows(DecryptionException::class, $message, $action);
        }

        // A missing key is a setup mistake and no failed decryption
        private function assertKeyIsRejected(callable $action): void {
            $this->assertThrows(RuntimeException::class, self::ERROR_KEY_LENGTH, $action);
        }

        private function decodePayload(string $encrypted): string {
            return base64_decode(strtr(substr($encrypted, strlen(self::HEADER)), "-_", "+/"));
        }

        private function encodePayload(string $payload): string {
            return rtrim(strtr(base64_encode($payload), "+/", "-_"), "=");
        }
    }

?>
