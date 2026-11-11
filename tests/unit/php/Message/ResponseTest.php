<?php

    namespace Tests\Message;

    use Tests\TestCase;

    class ResponseTest extends TestCase {

        private const SETTING = "login_scope_allow_subdomains";

        protected function setUp(): void {
            $this->bootFramework(["host" => "http://localhost:8080"]);
        }

        // cookie domain scope (security)

        public function testGetCookieDomainScopeReturnsAnEmptyStringWhenSubdomainSharingIsDisabled(): void {
            $this->request();

            $this->assertSame("", $this->response()->getCookieDomainScope());
        }

        /** @dataProvider disabledValues */
        public function testGetCookieDomainScopeReturnsAnEmptyStringForAnyValueButTrue(string $value): void {
            $this->setSettings([self::SETTING => $value]);
            $this->request();

            $this->assertSame("", $this->response()->getCookieDomainScope());
        }

        public static function disabledValues(): array {
            return [
                "false" => ["false"],
                "an empty value" => [""],
                "a value that is not true" => ["no"],
            ];
        }

        // An unquoted `true`, `on` or `yes` in the ini file arrives as "1"
        /** @dataProvider enabledValues */
        public function testGetCookieDomainScopeIsEnabledByEverySpellingOfTrue(mixed $value): void {
            $this->setSettings([self::SETTING => $value]);
            $this->request();

            $this->assertSame(".localhost", $this->response()->getCookieDomainScope());
        }

        public static function enabledValues(): array {
            return [
                "the quoted string" => ["true"],
                "an unquoted true of the ini file" => [parse_ini_string("key = true")["key"]],
                "an unquoted on of the ini file" => [parse_ini_string("key = on")["key"]],
                "an environment override" => ["TRUE"],
            ];
        }

        // An unquoted `false`, `off` or `no` in the ini file arrives as ""
        public function testGetCookieDomainScopeStaysDisabledByAnUnquotedFalseOfTheIniFile(): void {
            $this->setSettings([self::SETTING => parse_ini_string("key = false")["key"]]);
            $this->request();

            $this->assertSame("", $this->response()->getCookieDomainScope());
        }

        public function testGetCookieDomainScopeReturnsDotDomainWhenSubdomainSharingIsEnabled(): void {
            $this->setSettings([self::SETTING => "true"]);
            $request = $this->request();

            $scope = $this->response()->getCookieDomainScope();

            // The leading dot is the wildcard signal to browsers: send the cookie to all subdomains
            $this->assertSame("." . $request->getDomain(), $scope);
            $this->assertSame(".localhost", $scope);
            $this->assertSame(".", $scope[0]);
        }

        /** @dataProvider hostsOfAnEnabledScope */
        public function testGetCookieDomainScopeFollowsTheConfiguredHost(string $host, string $expected): void {
            $this->setSettings([self::SETTING => "true", "host" => $host]);
            $this->request();

            $this->assertSame($expected, $this->response()->getCookieDomainScope());
        }

        public static function hostsOfAnEnabledScope(): array {
            return [
                "a subdomain with a port and a trailing slash" => ["https://api.example.com:8443/", ".api.example.com"],
                "an IPv4 address" => ["http://192.168.1.10", ".192.168.1.10"],
            ];
        }
    }

?>
