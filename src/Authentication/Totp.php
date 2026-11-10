<?php

    namespace ZubZet\Framework\Authentication;

    use InvalidArgumentException;

    /**
     * Time based one time passwords as authenticator apps expect them (RFC 6238):
     * HMAC-SHA1, six digits, a new code every 30 seconds.
     */
    final class Totp {

        // Seconds one code is valid for
        public const PERIOD = 30;

        // Length of a code
        private const DIGITS = 6;

        // RFC 4648 Base32, the encoding authenticators take a secret in
        private const ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";

        private string $secret;
        private string $label;
        private string $issuer;

        /**
         * @param string $secret The shared secret, Base32 encoded
         * @param string $label The account the authenticator shows, usually the email
         * @param string $issuer The application the authenticator shows
         */
        public function __construct(string $secret, string $label = "", string $issuer = "") {
            $this->secret = $secret;

            // A colon separates the issuer from the label in the provisioning uri
            $this->label = str_replace(":", "", $label);
            $this->issuer = str_replace(":", "", $issuer);
        }

        public static function create(string $secret, string $label = "", string $issuer = ""): self {
            return new self($secret, $label, $issuer);
        }

        /**
         * A fresh secret of 160 bits, the length RFC 4226 recommends
         *
         * @return string 32 Base32 characters
         */
        public static function generateSecret(): string {
            return self::base32Encode(random_bytes(20));
        }

        public function getSecret(): string {
            return $this->secret;
        }

        /**
         * The uri an authenticator reads from a QR code
         *
         * @return string An otpauth:// uri carrying the secret, the label and the issuer
         */
        public function getProvisioningUri(): string {
            $parameters = ["secret" => $this->secret];
            $name = $this->label;

            if("" !== $this->issuer) {
                $parameters = ["issuer" => $this->issuer] + $parameters;
                $name = $this->issuer . ":" . $this->label;
            }

            return "otpauth://totp/" . rawurlencode($name)
                . "?" . http_build_query($parameters, "", "&", PHP_QUERY_RFC3986);
        }

        /**
         * The code of a point in time
         *
         * @param int $timestamp Unix timestamp
         * @return string Six digits, with leading zeros
         */
        public function at(int $timestamp): string {
            $counter = pack("J", intdiv($timestamp, self::PERIOD));
            $hash = hash_hmac("sha1", $counter, self::base32Decode($this->secret), true);

            // Dynamic truncation (RFC 4226): the last nibble picks four bytes, read without the sign bit
            $offset = ord($hash[19]) & 0x0F;
            $number = unpack("N", substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

            return str_pad((string) ($number % (10 ** self::DIGITS)), self::DIGITS, "0", STR_PAD_LEFT);
        }

        public function now(): string {
            return $this->at(time());
        }

        /**
         * Check a code, tolerating a clock that is a little off
         *
         * @param string $code The code from the authenticator
         * @param int $drift Periods before and after the timestamp that are accepted as well
         * @param ?int $timestamp Unix timestamp, now when left out
         * @return bool Whether the code matched
         */
        public function verify(string $code, int $drift = 1, ?int $timestamp = null): bool {
            $timestamp ??= time();
            $matched = false;

            // Every period is compared, so the answer takes the same time wherever the match is
            for($step = -$drift; $step <= $drift; $step++) {
                $expected = $this->at($timestamp + $step * self::PERIOD);
                $matched = hash_equals($expected, $code) || $matched;
            }

            return $matched;
        }

        private static function base32Encode(string $bytes): string {
            $bits = "";
            foreach(str_split($bytes) as $byte) {
                $bits .= str_pad(decbin(ord($byte)), 8, "0", STR_PAD_LEFT);
            }

            $encoded = "";
            foreach(str_split($bits, 5) as $chunk) {
                $encoded .= self::ALPHABET[bindec(str_pad($chunk, 5, "0"))];
            }

            return $encoded;
        }

        private static function base32Decode(string $encoded): string {
            $encoded = strtoupper(rtrim($encoded, "="));

            // Two characters are the least that hold a byte
            if(strlen($encoded) < 2 || strlen($encoded) !== strspn($encoded, self::ALPHABET)) {
                throw new InvalidArgumentException("The two factor secret is not valid Base32.");
            }

            $bits = "";
            foreach(str_split($encoded) as $character) {
                $bits .= str_pad(decbin(strpos(self::ALPHABET, $character)), 5, "0", STR_PAD_LEFT);
            }

            // Bits left over after the last full byte are padding
            $bytes = "";
            foreach(str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8) as $byte) {
                $bytes .= chr(bindec($byte));
            }

            return $bytes;
        }
    }
?>
