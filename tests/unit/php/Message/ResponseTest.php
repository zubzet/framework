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
