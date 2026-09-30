<?php

    namespace ZubZet\Framework\Security;

    use RuntimeException;

    /**
     * Symmetric encryption for secrets that have to be stored and read back, like
     * third-party credentials in the database. The key is the `encryption_key`
     * setting of z_settings.ini.
     *
     * AES-256-GCM is authenticated, so a wrong key or a modified ciphertext is
     * detected on decryption and rejected with a DecryptionException instead of
     * returning garbage.
     *
     * Format: `zenc1:` + base64(iv . tag . ciphertext). The prefix versions the
     * format, so the algorithm can change without breaking stored values.
     */
    final class Encryption {

        public const SETTING = "encryption_key";
        public const MIN_KEY_LENGTH = 32;

        private const PREFIX = "zenc1:";
        private const CIPHER = "aes-256-gcm";
        private const IV_LENGTH = 12;
        private const TAG_LENGTH = 16;
        private const KEY_INFO = "zubzet-framework-encryption-v1";

        /** Encrypt a plaintext with the configured key. */
        public static function encrypt(string $plaintext): string {
            $iv = random_bytes(self::IV_LENGTH);
            $tag = "";

            $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag, "", self::TAG_LENGTH);
            if(false === $ciphertext) {
                throw new RuntimeException("Encryption failed.");
            }

            return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
        }

        /**
         * Decrypt a value created by encrypt().
         *
         * @throws DecryptionException When the key is wrong or the value is malformed or modified
         */
        public static function decrypt(string $encrypted): string {
            // Check the format before touching the payload
            if(!str_starts_with($encrypted, self::PREFIX)) {
                throw new DecryptionException("The value is not in a known encryption format.");
            }

            $payload = base64_decode(substr($encrypted, strlen(self::PREFIX)), true);
            if(false === $payload || strlen($payload) < self::IV_LENGTH + self::TAG_LENGTH) {
                throw new DecryptionException("The encrypted value is malformed.");
            }

            $iv = substr($payload, 0, self::IV_LENGTH);
            $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
            $ciphertext = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);

            // A failing tag check means a wrong key or a modified value
            $plaintext = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
            if(false === $plaintext) {
                throw new DecryptionException("The value could not be decrypted, the key is wrong or the value was modified.");
            }

            return $plaintext;
        }

        /** Derive the 256-bit cipher key from the configured setting. */
        private static function key(): string {
            $secret = (string) config(self::SETTING, default: "");
            if(strlen($secret) < self::MIN_KEY_LENGTH) {
                throw new RuntimeException("The setting '" . self::SETTING . "' must be at least " . self::MIN_KEY_LENGTH . " characters long.");
            }

            return hash_hkdf("sha256", $secret, 32, self::KEY_INFO);
        }
    }
?>
