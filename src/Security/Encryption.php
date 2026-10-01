<?php

    namespace ZubZet\Framework\Security;

    use RuntimeException;
    use ZubZet\Framework\Security\Cipher\Cipher;
    use ZubZet\Framework\Security\Cipher\AesGcmCipher;

    /**
     * Symmetric encryption for secrets that have to be stored and read back, like
     * third-party credentials in the database. The key is the `encryption_key`
     * setting of z_settings.ini.
     *
     * Format: `zenc:<cipher>:` + base64url(payload). Every value names its cipher,
     * so a new DEFAULT_CIPHER only affects new values while stored ones stay
     * readable as long as their cipher remains in CIPHERS. The cipher key is
     * derived per cipher, so a value relabeled with another cipher fails to decrypt.
     */
    final class Encryption {

        public const SETTING = "encryption_key";
        public const MIN_KEY_LENGTH = 32;

        private const MARKER = "zenc";
        private const DEFAULT_CIPHER = "aes-256-gcm";

        /** Released identifiers are stored in values and must never change. */
        private const CIPHERS = [
            "aes-256-gcm" => AesGcmCipher::class,
        ];

        public static function encryptSecret(string $plaintext): string {
            $payload = self::cipher(self::DEFAULT_CIPHER)->encrypt(self::key(self::DEFAULT_CIPHER), $plaintext);
            return self::MARKER . ":" . self::DEFAULT_CIPHER . ":" . self::base64UrlEncode($payload);
        }

        /**
         * Decrypt a value created by encryptSecret().
         *
         * @throws DecryptionException When the key is wrong or the value is malformed or modified
         */
        public static function decryptSecret(string $encrypted): string {
            $parts = explode(":", $encrypted, 3);
            if(3 !== count($parts) || self::MARKER !== $parts[0]) {
                throw new DecryptionException("The value is not in a known encryption format.");
            }

            [, $cipherId, $encoded] = $parts;
            if(!isset(self::CIPHERS[$cipherId])) {
                throw new DecryptionException("The value was encrypted with the unknown cipher '$cipherId'.");
            }

            $payload = self::base64UrlDecode($encoded);
            if(null === $payload) {
                throw new DecryptionException("The encrypted value is malformed.");
            }

            return self::cipher($cipherId)->decrypt(self::key($cipherId), $payload);
        }

        private static function cipher(string $id): Cipher {
            $class = self::CIPHERS[$id];
            return new $class();
        }

        /** Derive a 256-bit key for one cipher from the configured setting. */
        private static function key(string $cipherId): string {
            $secret = (string) config(self::SETTING, default: "");
            if(strlen($secret) < self::MIN_KEY_LENGTH) {
                throw new RuntimeException("The setting '" . self::SETTING . "' must be at least " . self::MIN_KEY_LENGTH . " characters long.");
            }

            return hash_hkdf("sha256", $secret, 32, "zubzet-framework-encryption:" . $cipherId);
        }

        private static function base64UrlEncode(string $bytes): string {
            return rtrim(strtr(base64_encode($bytes), "+/", "-_"), "=");
        }

        private static function base64UrlDecode(string $encoded): ?string {
            if(!preg_match('/^[A-Za-z0-9_-]*$/', $encoded)) return null;

            $bytes = base64_decode(strtr($encoded, "-_", "+/"), true);
            return false === $bytes ? null : $bytes;
        }
    }
?>
