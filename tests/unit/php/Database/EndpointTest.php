<?php

    namespace Tests\Database;

    use RuntimeException;
    use Tests\TestCase;
    use ZubZet\Framework\Database\Endpoint;

    class EndpointTest extends TestCase {

        private const ERROR_HOST_WITH_PORT = "db_ssl verifies the server certificate against 'dbhost', which must be a plain hostname: move the port into 'dbport'.";

        // TLS

        public function testRejectsAHostPortDbhostWithAPointerToDbport(): void {
            $this->bootFramework(["db_ssl" => "1", "dbhost" => "database:3306"]);

            $this->assertThrows(RuntimeException::class, self::ERROR_HOST_WITH_PORT, fn() => Endpoint::get());
        }

        /** @dataProvider hostsWithAColon */
        public function testRejectsEveryDbhostContainingAColonUnderTls(string $host): void {
            $this->bootFramework(["db_ssl" => "1", "dbhost" => $host]);

            $this->assertThrows(RuntimeException::class, self::ERROR_HOST_WITH_PORT, fn() => Endpoint::get());
        }

        public static function hostsWithAColon(): array {
            return [
                "an ipv6 literal" => ["::1"],
                "a bracketed ipv6 literal" => ["[::1]"],
                "a persistent prefix" => ["p:database"],
                "a trailing colon" => ["database:"],
            ];
        }

        public function testAcceptsAPlainHostnameUnderTls(): void {
            $this->bootFramework(["db_ssl" => "1", "dbhost" => "database", "dbport" => "3307"]);

            $endpoint = Endpoint::get();

            $this->assertSame("database", $endpoint->host);
            $this->assertSame(3307, $endpoint->port);
            $this->assertTrue($endpoint->tls);
        }

        public function testAcceptsAHostPortDbhostWithoutTls(): void {
            $this->bootFramework(["dbhost" => "database:3306"]);

            $endpoint = Endpoint::get();

            $this->assertSame("database:3306", $endpoint->host);
            $this->assertFalse($endpoint->tls);
        }

        /** @dataProvider tlsSettings */
        public function testReadsTheTlsSetting(string $value, bool $expected): void {
            $this->bootFramework(["dbhost" => "database", "db_ssl" => $value]);

            $this->assertSame($expected, Endpoint::get()->tls);
        }

        public static function tlsSettings(): array {
            return [
                "1" => ["1", true],
                "true" => ["true", true],
                "yes" => ["yes", true],
                "on" => ["on", true],
                "0" => ["0", false],
                "an empty string" => ["", false],
                "off" => ["off", false],
                "false as text, as an environment variable sets it" => ["false", false],
            ];
        }

        // A typo used to switch the encryption off without a word
        public function testRejectsATlsSettingThatIsNoSwitch(): void {
            $this->bootFramework(["dbhost" => "database", "db_ssl" => "ture"]);

            $this->assertThrows(
                \InvalidArgumentException::class,
                "Config key 'db_ssl' must be a switch (true or false), got: 'ture'",
                fn() => Endpoint::get(),
            );
        }

        public function testTlsIsOffWithoutTheSetting(): void {
            $this->bootFramework(["dbhost" => "database"]);

            $this->assertFalse(Endpoint::get()->tls);
        }

        // Port

        /** @dataProvider ports */
        public function testReadsThePortSetting(string $value, ?int $expected): void {
            $this->bootFramework(["dbhost" => "database", "dbport" => $value]);

            $this->assertSame($expected, Endpoint::get()->port);
        }

        public static function ports(): array {
            return [
                "a number" => ["3306", 3306],
                "a fractional number" => ["3306.9", 3306],
                "a negative number" => ["-1", -1],
                "text" => ["abc", null],
                "an empty string" => ["", null],
            ];
        }

        public function testThePortIsUnsetWithoutTheSetting(): void {
            $this->bootFramework(["dbhost" => "database"]);

            $this->assertNull(Endpoint::get()->port);
        }

        // Singleton

        public function testReadsTheSettingsOnlyOnce(): void {
            $this->bootFramework(["dbhost" => "first"]);
            $endpoint = Endpoint::get();

            $this->setSettings(["dbhost" => "second"]);

            $this->assertSame($endpoint, Endpoint::get());
            $this->assertSame("first", Endpoint::get()->host);
        }
    }

?>
