<?php

    namespace Tests\Authentication;

    use Tests\TestCase;
    use ZubZet\Framework\Authentication\LoginToken;

    class LoginTokenTest extends TestCase {

        public function testAGeneratedTokenIsStoredHashed(): void {
            $token = LoginToken::generate();
            $stored = LoginToken::hash($token);

            $this->assertNotSame($token, $stored);
            $this->assertFalse(LoginToken::isPlain($stored));
        }

        public function testALegacyTokenIsStoredAsIs(): void {
            $this->assertSame("0123abcd", LoginToken::hash("0123abcd"));
            $this->assertTrue(LoginToken::isPlain("0123abcd"));
        }

        // The token column compares case-insensitively: an upper-cased stored hash sent as a
        // token must be hashed again rather than looked up as is, or it would match its own row
        public function testAStoredHashInAnotherCasingIsNotAPlainToken(): void {
            $stored = LoginToken::hash(LoginToken::generate());
            $upperCased = strtoupper($stored);

            $this->assertFalse(LoginToken::isPlain($upperCased));
            $this->assertNotSame(strtolower($upperCased), strtolower(LoginToken::hash($upperCased)));
        }
    }

?>
