<?php

    namespace ZubZet\Framework\Authentication\PasswordHash;

    /**
     * Verify-only shim for passwords hashed by WordPress 6.8 and later (`$wp$2y$…`):
     * bcrypt over an HMAC-SHA-384 pre-hash of the trimmed plaintext, keyed with a
     * fixed string. Keeps imported `wordpress` rows verifiable so they self-heal to
     * a native hash on login (see {@see Password}).
     *
     * Mirrors wp_hash_password() in WordPress 6.8, including its trim():
     * https://github.com/WordPress/WordPress/blob/6.8/wp-includes/pluggable.php#L2648-L2678
     *
     * @internal
     * @deprecated Transition-only; remove once no `wordpress` rows remain.
     */
    final class WordPressBcryptHash {

        private const PREFIX = "\$wp";
        private const HMAC_KEY = "wp-sha384";

        /** Verify a plaintext against a stored `$wp$…` hash; any other format is wrong. */
        public static function verify(string $password, string $stored): bool {
            if(!str_starts_with($stored, self::PREFIX)) return false;
            $preHash = base64_encode(hash_hmac("sha384", trim($password), self::HMAC_KEY, true));
            return password_verify($preHash, substr($stored, \strlen(self::PREFIX)));
        }
    }
?>
