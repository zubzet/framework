<?php

    namespace ZubZet\Framework\Security\Cipher;

    use RuntimeException;
    use ZubZet\Framework\Security\DecryptionException;

    /** AES-256-GCM through OpenSSL. Payload layout: iv (12) . tag (16) . ciphertext. */
    final class AesGcmCipher implements Cipher {

        private const ALGORITHM = "aes-256-gcm";
        private const IV_LENGTH = 12;
        private const TAG_LENGTH = 16;

        public function encrypt(string $key, string $plaintext): string {
            $iv = random_bytes(self::IV_LENGTH);
            $tag = "";

            $ciphertext = openssl_encrypt($plaintext, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv, $tag, "", self::TAG_LENGTH);
            if(false === $ciphertext) {
                throw new RuntimeException("Encryption failed.");
            }

            return $iv . $tag . $ciphertext;
        }

        public function decrypt(string $key, string $payload): string {
            if(strlen($payload) < self::IV_LENGTH + self::TAG_LENGTH) {
                throw new DecryptionException("The encrypted value is malformed.");
            }

            $iv = substr($payload, 0, self::IV_LENGTH);
            $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
            $ciphertext = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);

            // A failing tag check means a wrong key or a modified value
            $plaintext = openssl_decrypt($ciphertext, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv, $tag);
            if(false === $plaintext) {
                throw new DecryptionException("The value could not be decrypted, the key is wrong or the value was modified.");
            }

            return $plaintext;
        }

    }

?>
