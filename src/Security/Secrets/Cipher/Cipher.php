<?php

    namespace ZubZet\Framework\Security\Secrets\Cipher;

    use ZubZet\Framework\Security\Secrets\DecryptionException;

    /**
     * An authenticated cipher behind Encryption. Implementations must detect a
     * wrong key or a modified payload, so only AEAD algorithms qualify.
     */
    interface Cipher {

        /**
         * @param string $key 32 raw bytes
         * @return string Raw bytes, including everything decrypt() needs besides the key
         */
        public function encrypt(string $key, string $plaintext): string;

        /**
         * @param string $key 32 raw bytes
         * @throws DecryptionException When the key is wrong or the payload is malformed or modified
         */
        public function decrypt(string $key, string $payload): string;

    }

?>
