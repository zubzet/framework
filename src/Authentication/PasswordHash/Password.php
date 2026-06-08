<?php

    namespace ZubZet\Framework\Authentication\PasswordHash;

    use InvalidArgumentException;

    /**
     * Password hashing on PHP's native Argon2id (at PHP's default cost), the public
     * entry point for the framework's password handling. A `password_scheme` marker
     * lets legacy and onion-wrapped hashes coexist and self-heal to native on login.
     *
     * - native: `password` is a password_hash() string; `salt` is null.
     * - legacy: `password` is the SHA-512 hex; `salt` is the per-row salt.
     * - onion : `password` is a native hash of the SHA-512 hex; `salt` is kept.
     */
    final class Password {

        public const NATIVE = "native";
        public const LEGACY = "legacy";
        public const ONION = "onion";

        // The max is a DoS guard (rejected, never truncated).
        public const MIN_LENGTH_BYTES = 3;
        public const MAX_LENGTH_BYTES = 1024;

        /**
         * Reduced cost for the onion layer only. An un-peeled onion row is verified
         * up to 26 times (the legacy a-z "pepper" was never stored), so the default
         * cost would make one login multi-second and a DoS vector. Still far
         * stronger than the bare SHA-512 it wraps, and peeled to the default on the
         * next login.
         */
        private const ONION_OPTS = [
            "memory_cost" => 12288,
            "time_cost" => 1,
            "threads" => 1,
        ];

        /** Create a native hash from a plaintext password. */
        public static function hash(string $password): string {
            // Assert the correct length
            $length = strlen($password);
            if(self::MIN_LENGTH_BYTES > $length || self::MAX_LENGTH_BYTES < $length) {
                throw new InvalidArgumentException("Invalid password length.");
            }

            // Generate a new native hash
            return password_hash($password, PASSWORD_ARGON2ID);
        }

        /**
         * Verify a plaintext against a stored credential. `scheme` selects how to
         * read the stored value (defaults to native); `salt` is only needed for
         * legacy/onion rows.
         */
        public static function verify(string $password, string $stored, ?string $scheme = self::NATIVE, ?string $salt = null): Verification {
            // Make sure the length is correct
            $length = strlen($password);
            if(empty($stored) || self::MIN_LENGTH_BYTES > $length || self::MAX_LENGTH_BYTES < $length) {
                return Verification::createWrong();
            }

            // Verifying using the current native method
            if(self::NATIVE === $scheme) {
                return self::verifyNative($password, $stored);
            }

            // Verifying using the old hash method
            if(self::LEGACY === $scheme || self::ONION === $scheme) {
                if(empty($salt)) {
                    throw new InvalidArgumentException("A legacy or onion hash requires a salt.");
                }

                return self::verifyLegacy($password, $salt, $stored);
            }

            // Otherwise, it can't be verified, so default wrong
            return Verification::createWrong();
        }

        /**
         * Wrap a stored legacy SHA-512 hex in a native hash without the plaintext.
         * @internal The bulk-migration helper; not part of the public verify/hash API.
         */
        public static function onionWrap(string $legacyHash): string {
            return password_hash($legacyHash, PASSWORD_ARGON2ID, self::ONION_OPTS);
        }

        /** Native Argon2id: matches, and self-heals when below the current cost. */
        private static function verifyNative(string $password, string $stored): Verification {
            // Check if the password is wrong with the native password function
            if(false === password_verify($password, $stored)) {
                return Verification::createWrong();
            }

            // If the password is correct, check that no change in hash is needed
            if(false === password_needs_rehash($stored, PASSWORD_ARGON2ID)) {
                return Verification::createCorrect();
            }

            // Otherwise the password is correct, but needs an upgrade, provide the new hash
            return Verification::createCorrectWithUpgrade($password);
        }

        /** Legacy or onion (the shim detects which); any match self-heals to native. */
        private static function verifyLegacy(string $password, string $salt, string $stored): Verification {
            // Check if the password was wrong
            if(false === LegacyHash::verify($password, $salt, $stored)) {
                return Verification::createWrong();
            }

            // Else, it is correct, but an upgrade is always needed for legacy hashes
            return Verification::createCorrectWithUpgrade($password);
        }
    }
?>
