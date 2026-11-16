<?php

    namespace Tests\Security;

    use RuntimeException;
    use Tests\TestCase;
    use ZubZet\Framework\Security\CSRF;

    class CSRFTest extends TestCase {

        private const PASS = null;
        private const CROSS = "cross site request";
        private const BLOCKED = "blocked origin";

        private const SAME_SITE = ["csrf_allow" => "same-site"];
        private const SITES = ["same-origin", "none", "same-site", "cross-site"];
        private const SAFE_METHODS = ["GET", "HEAD", "OPTIONS"];
        private const CHECKED_METHODS = ["POST", "PUT", "PATCH", "DELETE"];

        private const ERROR_INVALID_ALLOW = "Invalid csrf_allow setting: ";

        // The decision

        /**
         * @dataProvider decisionsWithoutSecFetchSite
         */
        public function testPassesWithoutSecFetchSiteUnlessTheSourceIsBlockedOrUnknown(?string $expected, array $headers, array $settings = []): void {
            // Old browsers, plain http, curl, servers and webhooks
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsWithoutSecFetchSite(): array {
            return [
                "no headers" => [self::PASS, []],
                "a plain Origin" => [self::PASS, ["Origin" => "https://evil.test"]],
                "a blocked Origin" => [self::BLOCKED, ["Origin" => "https://bucket.example.com"], ["csrf_blocked_origins" => "https://bucket.example.com"]],
                // A hidden source could be the blocked one
                "a hidden Origin next to a blocklist" => [self::CROSS, ["Origin" => "null"], ["csrf_blocked_origins" => "https://bucket.example.com"]],
                "a hidden Origin" => [self::CROSS, ["Origin" => "null"]],
            ];
        }

        /**
         * @dataProvider decisionsOfTheSource
         */
        public function testTakesTheSourceFromOrigin(?string $expected, array $headers, array $settings = []): void {
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfTheSource(): array {
            $trusted = ["csrf_trusted_origins" => "https://partner.test"];

            return [
                "the trusted Origin" => [self::PASS, ["Sec-Fetch-Site" => "cross-site", "Origin" => "https://partner.test"], $trusted],
                "the trusted Origin in other case" => [self::PASS, ["Sec-Fetch-Site" => "cross-site", "Origin" => "https://Partner.TEST"], $trusted],
                // Origin: null and a missing Origin leave it unknown
                "Origin null" => [self::CROSS, ["Sec-Fetch-Site" => "cross-site", "Origin" => "null"], $trusted],
                "no Origin" => [self::CROSS, ["Sec-Fetch-Site" => "cross-site"], $trusted],
                "an Origin without a scheme" => [self::CROSS, ["Sec-Fetch-Site" => "cross-site", "Origin" => "partner.test"], $trusted],
            ];
        }

        /**
         * @dataProvider decisionsOfCsrfAllow
         */
        public function testLetsTheSecFetchSiteValuesOfCsrfAllowThroughAnythingElseOnlyFromATrustedSource(?string $expected, array $headers, array $settings = []): void {
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfCsrfAllow(): array {
            $expectations = [
                "same-origin" => [self::PASS, self::PASS, self::CROSS, self::CROSS],
                "same-site" => [self::PASS, self::PASS, self::PASS, self::CROSS],
                "any" => [self::PASS, self::PASS, self::PASS, self::PASS],
                // The value is case insensitive
                "Same-Site" => [self::PASS, self::PASS, self::PASS, self::CROSS],
            ];

            $cases = [];
            foreach($expectations as $allow => $expected) {
                foreach(self::SITES as $index => $site) {
                    $cases["csrf_allow $allow with $site"] = [$expected[$index], self::site($site, "https://www.example.com"), ["csrf_allow" => $allow]];
                }
            }

            // The value is case insensitive, any other one is not let through
            foreach(["Same-Origin", "NONE"] as $site) {
                $cases["Sec-Fetch-Site $site"] = [self::PASS, self::site($site, "https://app.example.com"), self::SAME_SITE];
            }
            foreach(["same_origin", "same-origin, cross-site", "bogus"] as $site) {
                $cases["Sec-Fetch-Site $site"] = [self::CROSS, self::site($site, "https://app.example.com"), self::SAME_SITE];
            }

            // A trusted source passes whatever Sec-Fetch-Site says
            $trusted = ["csrf_trusted_origins" => "https://www.example.com"];
            $cases["a trusted source from the same site"] = [self::PASS, self::site("same-site", "https://www.example.com"), $trusted];
            $cases["a trusted source from another site"] = [self::PASS, self::site("cross-site", "https://www.example.com"), $trusted];
            $cases["a trusted source from a bogus site"] = [self::PASS, self::site("bogus", "https://www.example.com"), $trusted];
            $cases["another host next to a trusted one"] = [self::CROSS, self::site("same-site", "https://www2.example.com"), $trusted];

            // Surrounding spaces and case of the header value do not matter
            $cases["Sec-Fetch-Site with spaces"] = [self::PASS, self::site(" same-origin ", "https://app.example.com")];
            $cases["Sec-Fetch-Site with spaces and a trusted source"] = [self::PASS, self::site(" CROSS-SITE ", "https://www.example.com"), $trusted];

            return $cases;
        }

        /**
         * @dataProvider invalidCsrfAllowRequests
         */
        public function testLetsTheSecFetchSiteValuesOfCsrfAllowThroughAnythingElseOnlyFromATrustedSourceThrowsForAnInvalidCsrfAllow(string $allow, array $headers): void {
            // An invalid csrf_allow is a misconfiguration and throws, whatever source the request has
            $this->assertThrows(RuntimeException::class, self::ERROR_INVALID_ALLOW . strtolower($allow), function() use ($allow, $headers) {
                $this->checkRequest($headers, ["csrf_allow" => $allow], "POST", []);
            });
        }

        public static function invalidCsrfAllowRequests(): array {
            $requests = [
                "a same origin request" => ["Sec-Fetch-Site" => "same-origin"],
                "a hidden Origin" => ["Sec-Fetch-Site" => "same-site", "Origin" => "null"],
                "an Origin only" => ["Origin" => "https://www.example.com"],
            ];

            $cases = [];
            foreach(["same_site", "cross-site", ""] as $allow) {
                foreach($requests as $name => $headers) {
                    $cases["csrf_allow \"$allow\" with $name"] = [$allow, $headers];
                }
            }

            // The setting is lowercased before it is checked and reported
            $cases["csrf_allow \"Same_Site\" is reported in lowercase"] = ["Same_Site", $requests["an Origin only"]];
            return $cases;
        }

        public function testFallsBackToSameOriginWithoutCsrfAllowSetting(): void {
            $this->bootFramework();
            $this->request($this->input()->withMethod("POST"));
            $this->assertTrue(CSRF::verify());

            foreach(["cross-site" => false, "same-site" => false, "same-origin" => true, "none" => true] as $site => $expected) {
                $this->bootFramework();
                $input = $this->input()->withMethod("POST");
                $input->SERVER["HTTP_SEC_FETCH_SITE"] = $site;
                $input->SERVER["HTTP_ORIGIN"] = "https://www.example.com";
                $this->request($input);

                $this->assertSame($expected, CSRF::verify(), "Sec-Fetch-Site $site");
            }
        }

        /**
         * @dataProvider emptyHeaders
         */
        public function testTreatsAnEmptyHeaderAsABrowserThatHidesItsSource(?string $expected, array $headers, array $settings = []): void {
            // Only a missing header means no browser, an empty one is a source nobody can tell
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function emptyHeaders(): array {
            return [
                "an empty Sec-Fetch-Site" => [self::CROSS, ["Sec-Fetch-Site" => ""]],
                "an empty Origin" => [self::CROSS, ["Origin" => ""]],
                "both empty" => [self::CROSS, ["Sec-Fetch-Site" => "", "Origin" => ""]],
                "an empty Sec-Fetch-Site under any" => [self::PASS, ["Sec-Fetch-Site" => ""], ["csrf_allow" => "any"]],
                "an empty Origin under any" => [self::PASS, ["Origin" => ""], ["csrf_allow" => "any"]],
            ];
        }

        /**
         * @dataProvider decisionsWithoutAKnownSource
         */
        public function testPassesARequestWithoutAKnownSourceOnlyFromTheSameOriginOrNoPage(?string $expected, array $headers, array $settings): void {
            // The lists only know the origins they list, a page that hides its Origin (no-referrer,
            // a sandboxed iframe) would slip past the blocklist, so it gets the strictest rules
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsWithoutAKnownSource(): array {
            $settings = array_merge(self::SAME_SITE, ["csrf_blocked_origins" => "https://bucket.example.com"]);

            return [
                "same-site without Origin" => [self::CROSS, ["Sec-Fetch-Site" => "same-site"], $settings],
                "same-site with Origin null" => [self::CROSS, ["Sec-Fetch-Site" => "same-site", "Origin" => "null"], $settings],
                "same-site with an empty Origin" => [self::CROSS, ["Sec-Fetch-Site" => "same-site", "Origin" => ""], $settings],
                "same-site with Origin NULL and spaces" => [self::CROSS, ["Sec-Fetch-Site" => "same-site", "Origin" => " NULL "], $settings],
                "cross-site with Origin null" => [self::CROSS, ["Sec-Fetch-Site" => "cross-site", "Origin" => "null"], $settings],
                "same-site with a known Origin" => [self::PASS, ["Sec-Fetch-Site" => "same-site", "Origin" => "https://www.example.com"], $settings],
                "same-origin with Origin null" => [self::PASS, ["Sec-Fetch-Site" => "same-origin", "Origin" => "null"], $settings],
                "none without Origin" => [self::PASS, ["Sec-Fetch-Site" => "none"], $settings],
                "no headers" => [self::PASS, [], $settings],
                // Even with nothing blocked
                "same-site without Origin and nothing blocked" => [self::CROSS, ["Sec-Fetch-Site" => "same-site"], self::SAME_SITE],
                "same-site with Origin null and nothing blocked" => [self::CROSS, ["Sec-Fetch-Site" => "same-site", "Origin" => "null"], self::SAME_SITE],
            ];
        }

        /**
         * @dataProvider decisionsOfBlockedSources
         */
        public function testRejectsABlockedSourceOnEverySecFetchSiteThatPasses(?string $expected, array $headers, array $settings): void {
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfBlockedSources(): array {
            $settings = array_merge(self::SAME_SITE, ["csrf_blocked_origins" => "https://bucket.example.com"]);

            $cases = [];
            foreach(["same-origin", "none", "same-site"] as $site) {
                $cases["$site from the blocked origin"] = [self::BLOCKED, self::site($site, "https://bucket.example.com"), $settings];
            }

            // Not let through by csrf_allow, only the trusted list decides
            $cases["cross-site from the blocked origin"] = [self::CROSS, self::site("cross-site", "https://bucket.example.com"), $settings];
            // Only the exact origin
            $cases["another scheme"] = [self::PASS, self::site("same-site", "http://bucket.example.com"), $settings];
            $cases["another port"] = [self::PASS, self::site("same-site", "https://bucket.example.com:8443"), $settings];
            $cases["a subdomain of it"] = [self::PASS, self::site("same-site", "https://x.bucket.example.com"), $settings];

            return $cases;
        }

        // The methods

        /**
         * @dataProvider decisionsOfMethods
         */
        public function testSkipsGetHeadAndOptionsAndChecksEveryOtherMethod(?string $expected, string $method): void {
            $this->assertDecision($expected, self::crossSite(), [], $method);
        }

        public static function decisionsOfMethods(): array {
            $cases = [];
            foreach(self::SAFE_METHODS as $method) {
                $cases[$method] = [self::PASS, $method];
            }
            foreach(self::CHECKED_METHODS as $method) {
                $cases[$method] = [self::CROSS, $method];
            }

            // The request method is case insensitive
            $cases["get"] = [self::PASS, "get"];
            $cases["post"] = [self::CROSS, "post"];

            // Beyond the original cases
            $cases["Options in mixed case"] = [self::PASS, "Options"];
            $cases["an unknown method"] = [self::CROSS, "TRACE"];

            return $cases;
        }

        public function testSkipsASafeMethodEvenForABlockedSource(): void {
            $settings = ["csrf_blocked_origins" => "https://evil.test"];

            $this->assertDecision(self::PASS, self::crossSite(), $settings, "GET");
            $this->assertDecision(self::BLOCKED, self::crossSite(), $settings, "POST");
        }

        public function testTreatsAMissingRequestMethodAsGet(): void {
            $this->assertTrue($this->checkRequest(self::crossSite(), [], null, []));
            $this->assertFalse($this->checkRequest(self::crossSite(), [], null, ["GET"]));
        }

        /**
         * @dataProvider decisionsOfAdditionalMethods
         */
        public function testChecksTheSafeMethodsPassedToItCaseInsensitive(?string $expected, string $method, array $additionalMethods): void {
            $this->assertDecision($expected, self::crossSite(), [], $method, $additionalMethods);
        }

        public static function decisionsOfAdditionalMethods(): array {
            return [
                "GET opted in" => [self::CROSS, "GET", ["GET"]],
                "HEAD stays exempt when only GET is opted in" => [self::PASS, "HEAD", ["GET"]],
                "GET opted in with get and head" => [self::CROSS, "GET", ["get", "head"]],
                "HEAD opted in with get and head" => [self::CROSS, "HEAD", ["get", "head"]],
                "OPTIONS stays exempt when get and head are opted in" => [self::PASS, "OPTIONS", ["get", "head"]],
                "POST with GET opted in" => [self::CROSS, "POST", ["GET"]],

                // Beyond the original cases
                "a mixed case name" => [self::CROSS, "GET", ["Get"]],
                "OPTIONS opted in" => [self::CROSS, "OPTIONS", ["OPTIONS"]],
                "a repeated name" => [self::CROSS, "GET", ["GET", "GET"]],
                "an unknown name leaves GET exempt" => [self::PASS, "GET", ["TRACE"]],
                "no names leave GET exempt" => [self::PASS, "GET", []],
            ];
        }

        /**
         * @dataProvider decisionsOfOptedInGet
         */
        public function testJudgesAnOptedInGetLikeAnyCheckedRequest(?string $expected, array $headers, array $settings): void {
            // A browser sends no Origin on a link, so an opted-in GET only passes from the same origin or no page
            $this->assertDecision($expected, $headers, $settings, "GET", ["GET"]);
        }

        public static function decisionsOfOptedInGet(): array {
            return [
                "same-origin" => [self::PASS, ["Sec-Fetch-Site" => "same-origin"], self::SAME_SITE],
                "none" => [self::PASS, ["Sec-Fetch-Site" => "none"], self::SAME_SITE],
                "no headers" => [self::PASS, [], self::SAME_SITE],
                "same-site" => [self::CROSS, ["Sec-Fetch-Site" => "same-site"], self::SAME_SITE],
                "cross-site next to a trusted origin" => [self::CROSS, ["Sec-Fetch-Site" => "cross-site"], ["csrf_trusted_origins" => "https://partner.test"]],
            ];
        }

        public function testOnlyValidatesCsrfAllowForACheckedRequest(): void {
            $headers = ["Sec-Fetch-Site" => "same-site"];
            $settings = ["csrf_allow" => "same_site"];

            $this->assertTrue($this->checkRequest($headers, $settings, "GET", []));
            $this->assertThrows(RuntimeException::class, self::ERROR_INVALID_ALLOW . "same_site", function() use ($headers, $settings) {
                $this->checkRequest($headers, $settings, "GET", ["GET"]);
            });
        }

        // The origin lists

        /**
         * @dataProvider decisionsOfListEntries
         */
        public function testMatchTheEntryExactlyAsTheBrowserSendsTheOrigin(?string $expected, array $headers, array $settings): void {
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfListEntries(): array {
            return [
                "an Origin with the default port" => [self::CROSS, self::cross("https://partner.test:443"), ["csrf_trusted_origins" => "https://partner.test"]],
                "an entry with the default port" => [self::CROSS, self::cross("https://partner.test"), ["csrf_trusted_origins" => "https://partner.test:443"]],
                "an entry with a trailing slash" => [self::CROSS, self::cross("https://partner.test"), ["csrf_trusted_origins" => "https://partner.test/"]],
                "an entry with a path" => [self::CROSS, self::cross("https://partner.test"), ["csrf_trusted_origins" => "https://partner.test/path"]],
                "an Origin with spaces" => [self::PASS, self::cross(" https://partner.test "), ["csrf_trusted_origins" => "https://partner.test"]],
                "an empty Origin" => [self::CROSS, self::cross(""), ["csrf_trusted_origins" => "https://partner.test"]],
            ];
        }

        /**
         * @dataProvider decisionsOfTrustedAndBlockedOrigins
         */
        public function testLetsATrustedOriginWinOverTheBlocklistAcrossSitesButNotWithinAllowedOnes(?string $expected, array $headers): void {
            $both = ["csrf_trusted_origins" => "https://partner.test", "csrf_blocked_origins" => "https://partner.test"];

            $this->assertDecision($expected, $headers, $both);
        }

        public static function decisionsOfTrustedAndBlockedOrigins(): array {
            return [
                "cross-site" => [self::PASS, self::cross("https://partner.test")],
                "same-origin" => [self::BLOCKED, self::site("same-origin", "https://partner.test")],
                "no Sec-Fetch-Site" => [self::BLOCKED, ["Origin" => "https://partner.test"]],
            ];
        }

        /**
         * @dataProvider decisionsUnderAny
         */
        public function testKeepsTheBlocklistUnderCsrfAllowAny(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers, ["csrf_allow" => "any", "csrf_blocked_origins" => "https://evil.test"]);
        }

        public static function decisionsUnderAny(): array {
            return [
                "another origin" => [self::PASS, self::cross("https://partner.test")],
                "the blocked origin" => [self::BLOCKED, self::cross("https://evil.test")],
                "a longer host" => [self::PASS, self::cross("https://EVIL.test.other")],
                // Every site may, so an unknown source has nothing to be told apart from
                "no Origin" => [self::PASS, ["Sec-Fetch-Site" => "cross-site"]],
                "cross-site with Origin null" => [self::PASS, self::cross("null")],
                "Origin null only" => [self::PASS, ["Origin" => "null"]],
            ];
        }

        /**
         * @dataProvider decisionsOfSpacesAndCase
         */
        public function testIgnoreCaseAndSpacesAroundAnEntry(?string $expected, array $headers, array $settings): void {
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfSpacesAndCase(): array {
            return [
                "a trusted entry" => [self::PASS, self::cross("https://partner.test"), ["csrf_trusted_origins" => " HTTPS://Partner.Test "]],
                "a blocked entry" => [self::BLOCKED, ["Origin" => "https://bucket.example.com"], ["csrf_blocked_origins" => "https://Bucket.Example.com"]],
            ];
        }

        /**
         * @dataProvider decisionsOfSchemeHostAndPort
         */
        public function testCompareSchemeHostAndPortExactly(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers, ["csrf_trusted_origins" => "https://partner.test"]);
        }

        public static function decisionsOfSchemeHostAndPort(): array {
            return [
                "another scheme" => [self::CROSS, self::cross("http://partner.test")],
                "another port" => [self::CROSS, self::cross("https://partner.test:8443")],
                "a subdomain" => [self::CROSS, self::cross("https://www.partner.test")],
                "a longer host" => [self::CROSS, self::cross("https://partner.test.evil.test")],
            ];
        }

        /**
         * @dataProvider decisionsOfEmptyAndInvalidEntries
         */
        public function testSkipEmptyEntriesAndMatchNoInvalidOne(?string $expected, array $headers, array $settings): void {
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfEmptyAndInvalidEntries(): array {
            $cases = [
                "entries around empty ones" => [self::PASS, self::cross("https://b.test"), ["csrf_trusted_origins" => " https://a.test ,, https://b.test, "]],
            ];

            foreach(["partner.test", "//partner.test", "*", "null", ""] as $entry) {
                $cases["the invalid entry \"$entry\""] = [self::CROSS, self::cross("https://partner.test"), ["csrf_trusted_origins" => "$entry, https://ok.test"]];
            }

            // A wildcard matches nothing
            $cases["a wildcard entry"] = [self::CROSS, self::cross("https://a.partner.test"), ["csrf_trusted_origins" => "https://*.partner.test"]];
            // An entry null never trusts an Origin: null, an unknown source is decided before the lists
            $cases["the entry null with Origin null"] = [self::CROSS, self::cross("null"), ["csrf_trusted_origins" => "null"]];
            // A blocklist of nothing but empty entries counts as empty
            $cases["a blocklist of empty entries"] = [self::PASS, self::site("same-site", "https://www.example.com"), array_merge(self::SAME_SITE, ["csrf_blocked_origins" => " , "])];

            // Beyond the original cases: an empty entry is nothing, so it never blocks or trusts a source that is missing
            $cases["a blocklist of empty entries and no Origin under any"] = [self::PASS, ["Sec-Fetch-Site" => "cross-site"], ["csrf_allow" => "any", "csrf_blocked_origins" => " , "]];
            $cases["a trusted list of empty entries and no Origin under any"] = [self::CROSS, ["Sec-Fetch-Site" => "bogus"], ["csrf_allow" => "any", "csrf_trusted_origins" => " , "]];
            $cases["the second entry of a blocklist"] = [self::BLOCKED, ["Origin" => "https://b.test"], ["csrf_blocked_origins" => " https://a.test ,, https://b.test, "]];

            return $cases;
        }

        // The documented scenarios

        /**
         * @dataProvider decisionsOfScenario1
         */
        public function test1TheSameApplicationOnSeveralSubdomains(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers);
        }

        public static function decisionsOfScenario1(): array {
            return [
                "a on same-origin" => [self::PASS, self::site("same-origin", "https://a.example.com")],
                "b on same-origin" => [self::PASS, self::site("same-origin", "https://b.example.com")],
                "a on same-site" => [self::CROSS, self::site("same-site", "https://a.example.com")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario2
         */
        public function test2ABucketOnASubdomainHostsForeignContentEveryOtherSubdomainMay(?string $expected, array $headers): void {
            $settings = array_merge(self::SAME_SITE, ["csrf_blocked_origins" => "https://bucket.example.com"]);

            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfScenario2(): array {
            return [
                "the bucket on same-site" => [self::BLOCKED, self::site("same-site", "https://bucket.example.com")],
                "the bucket without Sec-Fetch-Site" => [self::BLOCKED, ["Origin" => "https://bucket.example.com"]],
                // The bucket hiding its Origin cannot be told apart, so a hidden Origin is rejected
                "a hidden Origin" => [self::CROSS, self::site("same-site", "null")],
                "no Origin" => [self::CROSS, ["Sec-Fetch-Site" => "same-site"]],
                "another subdomain" => [self::PASS, self::site("same-site", "https://www.example.com")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario3
         */
        public function test3SubdomainXMaySubdomainYMayNot(?string $expected, array $headers, array $settings): void {
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfScenario3(): array {
            $trusted = ["csrf_trusted_origins" => "https://x.example.com"];
            $blocked = array_merge(self::SAME_SITE, ["csrf_blocked_origins" => "https://y.example.com"]);

            return [
                "x trusted" => [self::PASS, self::site("same-site", "https://x.example.com"), $trusted],
                "y next to a trusted x" => [self::CROSS, self::site("same-site", "https://y.example.com"), $trusted],
                // Without Sec-Fetch-Site only the blocklist applies
                "y without Sec-Fetch-Site next to a trusted x" => [self::PASS, ["Origin" => "https://y.example.com"], $trusted],
                "x next to a blocked y" => [self::PASS, self::site("same-site", "https://x.example.com"), $blocked],
                "y blocked" => [self::BLOCKED, self::site("same-site", "https://y.example.com"), $blocked],
                "y blocked without Sec-Fetch-Site" => [self::BLOCKED, ["Origin" => "https://y.example.com"], $blocked],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario4
         */
        public function test4OnlySiteXComMay(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers, ["csrf_trusted_origins" => "https://x.com"]);
        }

        public static function decisionsOfScenario4(): array {
            return [
                "x.com" => [self::PASS, self::site("cross-site", "https://x.com")],
                "www.x.com" => [self::CROSS, self::site("cross-site", "https://www.x.com")],
                "a subdomain of the application" => [self::CROSS, self::site("same-site", "https://www.example.com")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario5
         */
        public function test5OnlySiteXComAndSubdomainYMay(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers, ["csrf_trusted_origins" => "https://x.com, https://y.example.com"]);
        }

        public static function decisionsOfScenario5(): array {
            return [
                "x.com" => [self::PASS, self::site("cross-site", "https://x.com")],
                "y" => [self::PASS, self::site("same-site", "https://y.example.com")],
                "z" => [self::CROSS, self::site("same-site", "https://z.example.com")],
                "another site" => [self::CROSS, self::site("cross-site", "https://evil.test")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario6
         */
        public function test6EverySubdomainMayPlusSiteXCom(?string $expected, array $headers): void {
            $settings = array_merge(self::SAME_SITE, ["csrf_trusted_origins" => "https://x.com"]);

            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfScenario6(): array {
            return [
                "a subdomain" => [self::PASS, self::site("same-site", "https://www.example.com")],
                "same-site without Origin" => [self::CROSS, ["Sec-Fetch-Site" => "same-site"]],
                "x.com" => [self::PASS, self::site("cross-site", "https://x.com")],
                "cross-site with Origin null" => [self::CROSS, self::site("cross-site", "null")],
                "another site" => [self::CROSS, self::site("cross-site", "https://evil.test")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario7
         */
        public function test7EverySubdomainMayExceptX(?string $expected, array $headers): void {
            $settings = array_merge(self::SAME_SITE, ["csrf_blocked_origins" => "https://x.example.com"]);

            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfScenario7(): array {
            return [
                "another subdomain" => [self::PASS, self::site("same-site", "https://www.example.com")],
                "x" => [self::BLOCKED, self::site("same-site", "https://x.example.com")],
                // x hiding its Origin with no-referrer does not get past the blocklist
                "x hiding its Origin" => [self::CROSS, self::site("same-site", "null")],
                "same-site without Origin" => [self::CROSS, ["Sec-Fetch-Site" => "same-site"]],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario8
         */
        public function test8TheApplicationOnASubdomainTheParentHostMay(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers, ["csrf_trusted_origins" => "https://example.com"]);
        }

        public static function decisionsOfScenario8(): array {
            return [
                "the parent host" => [self::PASS, self::site("same-site", "https://example.com")],
                "a subdomain" => [self::CROSS, self::site("same-site", "https://www.example.com")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario9
         */
        public function test9TheApplicationOnASubdomainAnotherSubdomainMay(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers, ["csrf_trusted_origins" => "https://b.example.com"]);
        }

        public static function decisionsOfScenario9(): array {
            return [
                "b" => [self::PASS, self::site("same-site", "https://b.example.com")],
                "c" => [self::CROSS, self::site("same-site", "https://c.example.com")],
                "the parent host" => [self::CROSS, self::site("same-site", "https://example.com")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario10
         */
        public function test10TheNestedSubdomainXYMayAllOthersNot(?string $expected, array $headers): void {
            $this->assertDecision($expected, $headers, ["csrf_trusted_origins" => "https://x.y.example.com"]);
        }

        public static function decisionsOfScenario10(): array {
            return [
                "x.y" => [self::PASS, self::site("same-site", "https://x.y.example.com")],
                "y" => [self::CROSS, self::site("same-site", "https://y.example.com")],
                "a.x.y" => [self::CROSS, self::site("same-site", "https://a.x.y.example.com")],
            ];
        }

        /**
         * @dataProvider decisionsOfScenario11
         */
        public function test11TheSameHostMayOnPort3000NotOnPort4000(?string $expected, array $headers, array $settings): void {
            // Ports are not part of a site, a browser sends same-site across them
            $this->assertDecision($expected, $headers, $settings);
        }

        public static function decisionsOfScenario11(): array {
            $trusted = ["csrf_trusted_origins" => "https://app.example.com:3000"];
            $blocked = array_merge(self::SAME_SITE, ["csrf_blocked_origins" => "https://app.example.com:4000"]);

            return [
                "port 3000 trusted" => [self::PASS, self::site("same-site", "https://app.example.com:3000"), $trusted],
                "port 4000 next to a trusted 3000" => [self::CROSS, self::site("same-site", "https://app.example.com:4000"), $trusted],
                "port 3000 next to a blocked 4000" => [self::PASS, self::site("same-site", "https://app.example.com:3000"), $blocked],
                "port 4000 blocked" => [self::BLOCKED, self::site("same-site", "https://app.example.com:4000"), $blocked],
            ];
        }

        // Helpers

        private static function site(string $site, string $origin): array {
            return ["Sec-Fetch-Site" => $site, "Origin" => $origin];
        }

        private static function cross(string $origin): array {
            return self::site("cross-site", $origin);
        }

        private static function crossSite(): array {
            return self::cross("https://evil.test");
        }

        // Runs the decision as a POST unless the method says otherwise
        private function assertDecision(?string $expected, array $headers, array $settings = [], ?string $method = null, array $additionalMethods = []): void {
            $verified = $this->checkRequest($headers, $settings, $method ?? "POST", $additionalMethods);

            $this->assertSame(null === $expected, $verified, null === $expected ? "Expected the request to pass" : "Expected a $expected to be rejected");
        }

        // A null method leaves REQUEST_METHOD out of the request
        private function checkRequest(array $headers, array $settings, ?string $method, array $additionalMethods): bool {
            $this->bootFramework(array_merge([
                "csrf_allow" => "same-origin",
                "csrf_trusted_origins" => "",
                "csrf_blocked_origins" => "",
            ], $settings));

            $input = $this->input();
            foreach($headers as $name => $value) {
                $input->SERVER["HTTP_" . strtoupper(str_replace("-", "_", $name))] = $value;
            }
            if(null !== $method) $input->SERVER["REQUEST_METHOD"] = $method;
            $this->request($input);

            return CSRF::verify(...$additionalMethods);
        }
    }

?>
