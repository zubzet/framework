<?php

    namespace ZubZet\Framework\Authentication;

    final class LoginToken {

        // The method new tokens are issued with
        private const CURRENT_METHOD = "sha256";

        // Supported methods, each hashed by its hash_<method>()
        private const METHODS = [
            "plain",
            "sha256",
        ];

        // Generate a new token
        public static function generate(): string {
            return "zub-" . self::CURRENT_METHOD . "-" . bin2hex(random_bytes(32));
        }

        // The value z_logintoken stores
        public static function hash(string $token): string {
            // "zub-<method>-<hex>", legacy tokens "zub-<hex>"
            $parts = explode("-", $token, 3);
            $method = $parts[1] ?? "plain";

            // If the method is available, use it, otherwise fallback to plain
            if(!in_array($method, self::METHODS, true)) $method = "plain";

            $hash = self::{"hash_$method"}($token);

            // Legacy rows store the token as is
            if("plain" === $method) return $hash;

            return "zub-$method-$hash";
        }

        // Plain is not hashing anything (old zubzet versions used this)
        private static function hash_plain(string $token): string {
            return $token;
        }

        private static function hash_sha256(string $token): string {
            return hash("sha256", $token);
        }
    }
?>
