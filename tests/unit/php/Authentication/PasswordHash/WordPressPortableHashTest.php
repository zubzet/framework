<?php

    namespace Tests\Authentication\PasswordHash;

    use Tests\TestCase;
    use ZubZet\Framework\Authentication\PasswordHash\WordPressPortableHash;

    class WordPressPortableHashTest extends TestCase {

        // Produced by the original phpass class for "1205#demo"
        private const PASSWORD = "1205#demo";
        private const STORED = "\$P\$BtYtOPOLRoHt8wylQ6fJWJEKhqfXiG1";

        private const ITOA64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

        public function testVerifiesAHashOfThePhpassClass(): void {
            $this->assertTrue(WordPressPortableHash::verify(self::PASSWORD, self::STORED));
            $this->assertFalse(WordPressPortableHash::verify("wrong", self::STORED));
        }

        public function testTheHelperReproducesAKnownHash(): void {
            // The tests below build hashes the shim would reject, with this helper
            $this->assertSame(self::STORED, $this->phpassHash(self::PASSWORD, substr(self::STORED, 0, 12)));
        }

        public function testTrimsSurroundingWhitespace(): void {
            $this->assertTrue(WordPressPortableHash::verify(" \t\n\r\0\x0B" . self::PASSWORD . "\x0B\0\r\n\t ", self::STORED));
        }

        public function testKeepsWhitespaceInsideThePassword(): void {
            $this->assertFalse(WordPressPortableHash::verify("1205# demo", self::STORED));
        }

        public function testAcceptsTheHPrefix(): void {
            $stored = $this->phpassHash(self::PASSWORD, "\$H\$7abcdefgh");

            $this->assertTrue(WordPressPortableHash::verify(self::PASSWORD, $stored));
        }

        public function testRejectsAnotherPrefix(): void {
            $stored = $this->phpassHash(self::PASSWORD, "\$Q\$7abcdefgh");

            $this->assertFalse(WordPressPortableHash::verify(self::PASSWORD, $stored));
        }

        public function testAcceptsTheLowestRoundCount(): void {
            // 2^7 rounds, written as the character at position 7 of the alphabet
            $stored = $this->phpassHash(self::PASSWORD, "\$P\$" . self::ITOA64[7] . "abcdefgh");

            $this->assertTrue(WordPressPortableHash::verify(self::PASSWORD, $stored));
        }

        public function testRejectsARoundCountBelowTheMinimum(): void {
            $stored = $this->phpassHash(self::PASSWORD, "\$P\$" . self::ITOA64[6] . "abcdefgh");

            $this->assertFalse(WordPressPortableHash::verify(self::PASSWORD, $stored));
        }

        public function testRejectsAStoredValueCutOffInTheSalt(): void {
            $this->assertFalse(WordPressPortableHash::verify(self::PASSWORD, substr(self::STORED, 0, 11)));
        }

        public function testRejectsAStoredValueThatIsOnlyThePrefix(): void {
            $this->assertFalse(WordPressPortableHash::verify(self::PASSWORD, "\$P\$"));
        }

        /**
         * The hash phpass writes for a setting (identifier, round count character
         * and salt), without any of the checks the shim does on the setting.
         */
        private function phpassHash(string $password, string $setting): string {
            $count = 1 << strpos(self::ITOA64, $setting[3]);

            $hash = md5(substr($setting, 4, 8) . $password, true);
            do {
                $hash = md5($hash . $password, true);
            } while(--$count);

            return substr($setting, 0, 12) . $this->encode($hash);
        }

        // Three bytes become four characters, low bits first
        private function encode(string $bytes): string {
            $encoded = "";
            foreach(str_split($bytes, 3) as $group) {
                $value = 0;
                foreach(str_split($group) as $position => $byte) {
                    $value |= ord($byte) << (8 * $position);
                }

                // A group of n bytes needs n + 1 characters
                for($index = 0; $index <= strlen($group); $index++) {
                    $encoded .= self::ITOA64[($value >> (6 * $index)) & 0x3f];
                }
            }
            return $encoded;
        }
    }

?>
