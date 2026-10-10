<?php

    namespace ZubZet\Framework\Security\Secrets;

    use RuntimeException;
    use ZubZet\Framework\Security\Secrets\Cipher\AesGcmCipher;

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
            $cipherClass = self::CIPHERS[self::DEFAULT_CIPHER];
            $cipher = new $cipherClass();
            $key = self::key(self::DEFAULT_CIPHER);

            $payload = $cipher->encrypt($key, $plaintext);
            $encoded = self::base64UrlEncode($payload);

            return self::MARKER . ":" . self::DEFAULT_CIPHER . ":" . $encoded;
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

            $cipherClass = self::CIPHERS[$cipherId];
            $cipher = new $cipherClass();
            $key = self::key($cipherId);

            return $cipher->decrypt($key, $payload);
        }

        /** Derive a 256-bit key for one cipher from the configured setting. */
        private static function key(string $cipherId): string {
            $secret = (string) config(self::SETTING, default: "");
            if(strlen($secret) < self::MIN_KEY_LENGTH) {
                throw new RuntimeException("The setting '" . self::SETTING . "' must be at least " . self::MIN_KEY_LENGTH . " bytes long.");
            }

            return hash_hkdf("sha256", $secret, 32, "zubzet-framework-encryption:" . $cipherId);
        }

        private static function base64UrlEncode(string $bytes): string {
            // Plain base64 uses "+", "/" and "=", which need escaping in URLs, ini files and
            // environment variables. Base64url swaps in "-" and "_" and drops the padding, the
            // length alone tells how many bytes the last characters hold.
            return rtrim(strtr(base64_encode($bytes), "+/", "-_"), "=");
        }

        private static function base64UrlDecode(string $encoded): ?string {
            $bytes = base64_decode(strtr($encoded, "-_", "+/"), true);

            // Each character holds 6 bits. When the byte count is not a multiple of 3, the last
            // character has 2 or 4 bits left over, which base64_decode() ignores: "_w" and "_x"
            // both decode to the byte 0xff. AES-GCM only authenticates the decoded bytes, so such
            // a changed last character would still decrypt. Re-encoding yields the one canonical
            // spelling, and comparing it rejects every other spelling of the same bytes.
            if(false === $bytes || self::base64UrlEncode($bytes) !== $encoded) return null;

            return $bytes;
        }
    }
?>
