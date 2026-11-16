<?php

    namespace Tests\Authentication\PasswordHash;

    use Tests\TestCase;
    use ZubZet\Framework\Authentication\PasswordHash\WordPressBcryptHash;

    class WordPressBcryptHashTest extends TestCase {

        // Produced by a WordPress 6.8+ install for "1205#demo"
        private const PASSWORD = "1205#demo";
        private const STORED = "\$wp\$2y\$10\$EvwwMyCdG7u1ShgRw5RUAOkL2TWi/XP12tEdLLuFRZNcJJe9k3GB2";

        public function testVerifiesAHashOfWordPress68(): void {
            $this->assertTrue(WordPressBcryptHash::verify(self::PASSWORD, self::STORED));
            $this->assertFalse(WordPressBcryptHash::verify("wrong", self::STORED));
        }

        public function testTrimsEveryCharacterWordPressTrims(): void {
            $this->assertTrue(WordPressBcryptHash::verify(" \t\n\r\0\x0B" . self::PASSWORD . "\x0B\0\r\n\t ", self::STORED));
        }

        public function testKeepsWhitespaceInsideThePassword(): void {
            $this->assertFalse(WordPressBcryptHash::verify("1205# demo", self::STORED));
        }

        public function testRejectsAStoredValueWithoutTheWpPrefix(): void {
            // Anything after the first three characters is a valid bcrypt hash of the pre-hash here
            $this->assertFalse(WordPressBcryptHash::verify(self::PASSWORD, substr(self::STORED, 3)));
            $this->assertFalse(WordPressBcryptHash::verify(self::PASSWORD, "abc" . substr(self::STORED, 3)));
        }

        public function testRejectsAStoredValueThatIsOnlyThePrefix(): void {
            $this->assertFalse(WordPressBcryptHash::verify(self::PASSWORD, "\$wp"));
        }
    }

?>
