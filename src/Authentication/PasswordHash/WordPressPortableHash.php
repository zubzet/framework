<?php

    namespace ZubZet\Framework\Authentication\PasswordHash;

    /**
     * Verify-only shim for passwords hashed by WordPress before 6.8: the phpass
     * "portable" hash (`$P$` or `$H$`, 34 chars), salted MD5 iterated 2^count times
     * and encoded with phpass's own base64 alphabet. Keeps imported `wordpress` rows
     * verifiable so they self-heal to a native hash on login (see {@see Password}).
     *
     * cryptPrivate() and encode64() are line-for-line ports of class-phpass.php as
     * shipped with WordPress 6.8 (the "*" error markers became null):
     * https://github.com/WordPress/WordPress/blob/6.8/wp-includes/class-phpass.php#L94-L160
     * WordPress hashed trim($password), so the plaintext is trimmed here as well:
     * https://github.com/WordPress/WordPress/blob/6.7/wp-includes/pluggable.php#L2612-L2622
     *
     * @internal
     * @deprecated Transition-only; remove once no `wordpress` rows remain.
     */
    final class WordPressPortableHash {

        private const ITOA64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

        /** Verify a plaintext against a stored `$P$…` hash; any other format is wrong. */
        public static function verify(string $password, string $stored): bool {
            $computed = self::cryptPrivate(trim($password), $stored);
            return !is_null($computed) && hash_equals($stored, $computed);
        }

        private static function cryptPrivate(string $password, string $setting): ?string {
            $id = substr($setting, 0, 3);
            if($id !== "\$P\$" && $id !== "\$H\$") return null;

            $countLog2 = strpos(self::ITOA64, substr($setting, 3, 1));
            if(false === $countLog2 || $countLog2 < 7 || $countLog2 > 30) return null;

            $count = 1 << $countLog2;

            $salt = substr($setting, 4, 8);
            if(\strlen($salt) !== 8) return null;

            $hash = md5($salt . $password, true);
            do {
                $hash = md5($hash . $password, true);
            } while(--$count);

            return substr($setting, 0, 12) . self::encode64($hash, 16);
        }

        private static function encode64(string $input, int $count): string {
            $output = "";
            $i = 0;
            do {
                $value = ord($input[$i++]);
                $output .= self::ITOA64[$value & 0x3f];
                if($i < $count) {
                    $value |= ord($input[$i]) << 8;
                }
                $output .= self::ITOA64[($value >> 6) & 0x3f];
                if($i++ >= $count) {
                    break;
                }
                if($i < $count) {
                    $value |= ord($input[$i]) << 16;
                }
                $output .= self::ITOA64[($value >> 12) & 0x3f];
                if($i++ >= $count) {
                    break;
                }
                $output .= self::ITOA64[($value >> 18) & 0x3f];
            } while($i < $count);

            return $output;
        }
    }
?>
