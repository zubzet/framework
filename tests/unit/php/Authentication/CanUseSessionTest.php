<?php

    namespace Tests\Authentication;

    use Tests\TestCase;
    use ZubZet\Framework\Authentication\Session;

    class CanUseSessionTest extends TestCase {

        private const LONG_AGO = "2000-01-01 12:00:00";

        // Adds up to about 63 years, far beyond any timeout
        private const DECADES = 2000000000;

        protected function setUp(): void {
            $this->bootFramework(["loginTimeoutSeconds" => "43200"]);
        }

        // Add

        public function testGivesANewSessionTheSevenDayDefaultLifetimeWithoutLoginTimeoutSeconds(): void {
            $this->removeSetting("loginTimeoutSeconds");

            $session = $this->session(["created" => "2025-01-01 12:00:00"]);

            $this->assertSame("2025-01-08 12:00:00", $session->expiresAt());
        }

        public function testGivesANewSessionTheConfiguredLifetime(): void {
            $session = $this->session(["created" => "2025-01-01 12:00:00"]);

            $this->assertSame("2025-01-02 00:00:00", $session->expiresAt());
        }

        public function testAddsTheExtensionToTheLifetime(): void {
            $session = $this->session(["created" => "2025-01-01 12:00:00", "extended_seconds" => 300]);

            $this->assertSame("2025-01-02 00:05:00", $session->expiresAt());
        }

        // Business Logic

        public function testReportsARecentlyCreatedSessionAsNotExpired(): void {
            $session = $this->session(["created" => $this->secondsAgo(3600)]);

            $this->assertFalse($session->isExpired());
        }

        public function testReportsAnOldSessionWithoutExtensionAsExpired(): void {
            $session = $this->session(["created" => self::LONG_AGO]);

            $this->assertTrue($session->isExpired());
        }

        public function testReportsAnOldSessionAsNotExpiredWhenTheExtensionCoversTheGap(): void {
            $session = $this->session(["created" => self::LONG_AGO, "extended_seconds" => self::DECADES]);

            $this->assertFalse($session->isExpired());
        }

        // A fixed expiry

        public function testLetsAFixedExpiryOutrankTheComputedLifetime(): void {
            // Created long ago, so only the date keeps it alive
            $session = $this->session(["created" => self::LONG_AGO, "expires_at" => "2099-01-01 12:00:00"]);

            $this->assertFalse($session->isExpired());
            $this->assertSame("2099-01-01 12:00:00", $session->expiresAt());
            $this->assertTrue($session->canExpire());
        }

        public function testIgnoresTheTimeoutAndTheExtensionOnceAnExpiryIsFixed(): void {
            // Created just now and extended by decades on top
            $session = $this->session([
                "created" => $this->secondsAgo(0),
                "extended_seconds" => self::DECADES,
                "expires_at" => self::LONG_AGO,
            ]);

            $this->assertSame(self::DECADES, $session->extendedSeconds());
            $this->assertSame(self::LONG_AGO, $session->expiresAt());
            $this->assertTrue($session->isExpired());
        }

        public function testKeepsASessionExemptFromExpiringDespiteAFixedExpiry(): void {
            // Being exempt outranks the date, the session never becomes unusable
            $session = $this->session(["created" => self::LONG_AGO, "is_permanent" => 1, "expires_at" => self::LONG_AGO]);

            $this->assertFalse($session->isExpired());
            $this->assertNull($session->expiresAt());
            $this->assertFalse($session->canExpire());
        }

        private function session(array $row): Session {
            return new Session($row + [
                "id" => 1,
                "token" => "0400a00000000000000000000000000000000000",
                "userId" => 1,
                "userId_exec" => 1,
                "name" => null,
                "device" => null,
                "reason" => null,
                "ip_creation" => null,
                "ip_last" => null,
                "is_permanent" => 0,
                "is_apikey" => 0,
                "extended_seconds" => null,
                "expires_at" => null,
                "last_used" => null,
            ]);
        }

        private function secondsAgo(int $seconds): string {
            return date("Y-m-d H:i:s", time() - $seconds);
        }
    }

?>
