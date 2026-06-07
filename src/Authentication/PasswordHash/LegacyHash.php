<?php

    namespace ZubZet\Framework\Authentication\PasswordHash;

    /**
     * Verify-only shim for passwords from the retired `zubzet/password-hash-utilities`
     * library (the "0.9" + SHA-512 scheme). Keeps `legacy`/`onion` rows verifiable
     * so they self-heal to a native hash on login (see {@see Password}).
     *
     * The original scheme picked one random "pepper" character (a-z) and did not
     * store which, so verification tries all 26 variants.
     *
     * @internal
     * @deprecated Transition-only; remove once no `legacy`/`onion` rows remain.
     */
    final class LegacyHash {

        private const CHARSET = "abcdefghijklmnopqrstuvwxyz";

        /**
         * Verify a plaintext against a stored legacy credential: either the raw
         * SHA-512 hex (`legacy`) or that same digest wrapped in a native hash
         * (`onion`). The stored format self-describes which, so one method covers
         * both schemes.
         */
        public static function verify(string $password, string $salt, string $stored): bool {
            $match = false;
            foreach (self::innerHashes($password, $salt) as $candidate) {
                if (self::digestMatches($candidate, $stored)) {
                    $match = true;
                    // No break: keep timing uniform across the 26 candidates.
                }
            }
            return $match;
        }

        /** SHA-512 digests for every pepper variant (a-z) of the 0.9 scheme. */
        private static function innerHashes(string $password, string $salt): array {
            $out = [];
            foreach (str_split(self::CHARSET) as $pepper) {
                $out[] = hash("sha512", self::logic09($password, $salt, $pepper));
            }
            return $out;
        }

        /** Match one candidate digest against the stored value (raw hex, or onion-wrapped). */
        private static function digestMatches(string $candidate, string $stored): bool {
            if (str_starts_with($stored, "\$argon2")) {
                return password_verify($candidate, $stored);
            }
            return hash_equals($stored, $candidate);
        }

        /** Exact port of the old library's "0.9" custom logic. */
        private static function logic09(string $input, string $salt, string $pepper): string {
            $input = base64_encode($input) . $salt . $pepper;
            $input .= str_repeat(substr($input, 2), 2);
            $input = strrev($input);
            $key = $input;
            $result = "";
            $len = \strlen($input);
            for ($i = 0; $i < $len; $i++) {
                $char = substr($input, $i, 1);
                $keyCharacter = substr($key, ($i % \strlen($key)) - 1, 1);
                $char = chr(ord($char) + ord($keyCharacter));
                $result .= $char;
            }
            return base64_encode($result);
        }
    }
