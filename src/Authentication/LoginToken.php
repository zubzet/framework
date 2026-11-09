<?php

    namespace ZubZet\Framework\Authentication;

    final class LoginToken {

        // Plain is not hashing anything (old zubzet versions used this), a legacy row carries no prefix
        private const METHOD_PLAIN = "plain";

        // Hashing methods, each the prefix of the tokens it issues and of their stored values
        private const METHOD_SHA256 = "zub-sha256-";

        // Supported hashing methods, hash() knows how to compute each, the first method from the top
        // is always the method used to generate new tokens, the rest are only for verifying legacy rows
        private const METHODS = [
            self::METHOD_SHA256, // Current method
        ];

        // Generate a new token
        public static function generate(): string {
            return self::METHODS[0] . bin2hex(random_bytes(32));
        }

        // The value z_logintoken stores
        public static function hash(string $token): string {
            $method = self::method($token);

            // Legacy rows store the token as is
            if(self::METHOD_PLAIN === $method) return $token;

            $hash = match($method) {
                self::METHOD_SHA256 => hash("sha256", $token),
            };

            return $method . $hash;
        }

        // Whether a stored value is a token from before hashing, stored as is
        public static function isPlain(string $stored): bool {
            return self::METHOD_PLAIN === self::method($stored);
        }

        // The method named by the token's prefix, plain for anything without a known one
        private static function method(string $token): string {
            foreach(self::METHODS as $method) {
                if(str_starts_with($token, $method)) return $method;
            }

            return self::METHOD_PLAIN;
        }
    }
?>
