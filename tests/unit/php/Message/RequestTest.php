<?php

    namespace Tests\Message;

    use JsonException;
    use Tests\TestCase;
    use UnexpectedValueException;
    use ZubZet\Framework\Message\Request;

    class RequestTest extends TestCase {

        private const HOST = "http://localhost:8080";
        private const SLUG_URI = "/Core/readable/";
        private const FORM_ACTION = "target-action";

        private const ERROR_METHOD = "Method does not accept a JSON body";
        private const ERROR_CONTENT_TYPE = "Content-Type must be application/json";
        private const ERROR_CHARSET = "JSON must be UTF-8";
        private const ERROR_TOO_LARGE = "Body too large";
        private const ERROR_SYNTAX = "Syntax error";

        protected function setUp(): void {
            $this->bootFramework(["host" => self::HOST, "rootDirectory" => ""]);
        }

        // parse_url() fails on a path like "/x:1" and used to leave no url parts
        public function testUrlPartsKeepASegmentThatEndsInAColonAndDigits(): void {
            $this->assertSame(["Routing", "urlParts", "b:1"], $this->requestFor("/Routing/urlParts/b:1")->getUrlParts());
            $this->assertSame(["Routing", "urlParts", "b:1"], $this->requestFor("/Routing/urlParts/b:1?y=2")->getUrlParts());
        }

        public function testAPathEndingInAColonAndDigitsIsASingleSegment(): void {
            $this->assertSame(["anything:1"], $this->requestFor("/anything:1")->getUrlParts());
        }

        // Request::ip() walks the server variables for Client-IP, then X-Forwarded-For, then REMOTE_ADDR
        /** @dataProvider ipHeaders */
        public function testIpResolvesFromHeaderPriorityChain(array $server, string $expected): void {
            $input = $this->input();
            $input->SERVER = $server;

            $this->assertSame($expected, $this->request($input)->ip());
        }

        public static function ipHeaders(): array {
            return [
                "an explicit Client-IP header wins" => [
                    ["HTTP_CLIENT_IP" => "203.0.113.10", "HTTP_X_FORWARDED_FOR" => "198.51.100.20", "REMOTE_ADDR" => "172.18.0.1"],
                    "203.0.113.10",
                ],
                "no Client-IP, X-Forwarded-For wins" => [
                    ["HTTP_X_FORWARDED_FOR" => "198.51.100.30", "REMOTE_ADDR" => "172.18.0.1"],
                    "198.51.100.30",
                ],
                "no headers at all falls back to REMOTE_ADDR" => [
                    ["REMOTE_ADDR" => "172.18.0.1"],
                    "172.18.0.1",
                ],
            ];
        }

        public function testIpReturnsAForwardedChainUnsplit(): void {
            $input = $this->input();
            $input->SERVER = ["HTTP_X_FORWARDED_FOR" => "198.51.100.30, 10.0.0.1"];

            $this->assertSame("198.51.100.30, 10.0.0.1", $this->request($input)->ip());
        }

        public function testIpIsAnEmptyStringWithoutAnyAddress(): void {
            // The lookups in the process environment come last, make them find nothing
            $previous = getenv("HTTP_CLIENT_IP");
            putenv("HTTP_CLIENT_IP");

            try {
                $this->assertSame("", $this->request()->ip());
            } finally {
                if(false !== $previous) putenv("HTTP_CLIENT_IP=$previous");
            }
        }

        // The null case is observable: it stays distinct from an empty string
        public function testRefererReflectsTheRefererHeaderAndIsNullWhenAbsent(): void {
            $input = $this->input();
            $input->SERVER["HTTP_REFERER"] = "https://example.com/from?x=1";

            $this->assertSame("https://example.com/from?x=1", $this->request($input)->referer());
            $this->assertNull($this->request()->referer());
        }

        public function testUserAgentReturnsTheUserAgentHeaderVerbatim(): void {
            $agent = "UnitTest/1.0 (userAgent test)";
            $input = $this->input();
            $input->SERVER["HTTP_USER_AGENT"] = $agent;

            $this->assertSame($agent, $this->request($input)->userAgent());
        }

        public function testUserAgentIsNullWhenAbsent(): void {
            $this->assertNull($this->request()->userAgent());
        }

        // getExecutionTime() is microtime(true) minus REQUEST_TIME_FLOAT. A request that started
        // 250 ms ago reports at least that, without any sleeping
        public function testGetExecutionTimeIncreasesByTheInActionSleepDuration(): void {
            $fast = $this->requestStartedAgo(0.01)->getExecutionTime();
            $this->assertIsFloat($fast);
            $this->assertGreaterThanOrEqual(0.01, $fast);
            $this->assertLessThan(15, $fast);

            $slow = $this->requestStartedAgo(0.25)->getExecutionTime();
            $this->assertGreaterThanOrEqual(0.25, $slow);
            $this->assertLessThan(15, $slow);
        }

        public function testGetExecutionTimeIsNullWithoutAStartTime(): void {
            $this->assertNull($this->request()->getExecutionTime());
        }

        // The configured host is the canonical one, the request only adds its URI
        public function testGetCurrentUrlConcatenatesConfigHostWithTheRequestUri(): void {
            $this->assertSame(
                "http://localhost:8080/Core/currentUrl?foo=bar&baz=1",
                $this->requestFor("/Core/currentUrl?foo=bar&baz=1")->getCurrentURL(),
            );
        }

        public function testGetCurrentUrlIgnoresTheIncomingHostHeaderConfigHostWins(): void {
            $input = $this->input();
            $input->SERVER["REQUEST_URI"] = "/Core/currentUrl?prove=it";
            $input->SERVER["HTTP_HOST"] = "spoofed.attacker.example";

            $url = $this->request($input)->getCurrentURL();

            $this->assertSame("http://localhost:8080/Core/currentUrl?prove=it", $url);
            $this->assertStringNotContainsString("spoofed.attacker.example", $url);
        }

        // Setting zubzet()->host goes through HasDynamicAttributes::__set, config("host") reads it back
        public function testGetCurrentUrlPicksUpADynamicAttributesHostOverrideMidRequest(): void {
            $fakeHost = "https://prod.example.com";
            $uri = "/Core/currentUrl?hostOverride=" . rawurlencode($fakeHost);
            $request = $this->requestFor($uri);

            zubzet()->host = $fakeHost;

            $this->assertSame($fakeHost . $uri, $request->getCurrentURL());
        }

        public function testGetCurrentUrlIsTheHostAloneWithoutARequestUri(): void {
            $this->assertSame(self::HOST, $this->request()->getCurrentURL());
        }

        // getDomain() strips scheme, slashes and port from the configured host
        /** @dataProvider hostShapes */
        public function testGetDomainStripsSchemePortTrailingSlashAcrossHostShapes(string $host, string $expected): void {
            $this->setSettings(["host" => $host]);

            $this->assertSame($expected, $this->request()->getDomain());
        }

        public static function hostShapes(): array {
            return [
                "the default host" => [self::HOST, "localhost"],
                "a plain http url" => ["http://example.com", "example.com"],
                "a plain https url" => ["https://example.com", "example.com"],
                "a subdomain with a custom port and a trailing slash" => ["https://api.example.com:8443/", "api.example.com"],
                "no scheme, the port split still applies" => ["example.com:9000", "example.com"],
                "an IPv4 address with a port" => ["https://192.168.1.10:8080", "192.168.1.10"],
                "a bare host" => ["localhost", "localhost"],
                "a trailing slash without a port" => ["https://example.com/", "example.com"],
                "an empty host" => ["", ""],
            ];
        }

        public function testGetDomainIsEmptyWithoutAHostSetting(): void {
            $this->removeSetting("host");

            $this->assertSame("", $this->request()->getDomain());
        }

        // getReadableParameter() splits the slug on "-": the last chunk becomes the id,
        // the chunks before it, joined with "-", become the text
        public function testGetReadableParameter0TypicalSeoSlugSplitsIdOffTheTail(): void {
            $this->assertSame(
                ["id" => "64", "text" => "this-is-some-text"],
                $this->requestFor(self::SLUG_URI . "this-is-some-text-64")->getReadableParameter(0),
            );
        }

        public function testGetReadableParameter0TwoSegmentSlugIdIsTheTrailingToken(): void {
            $this->assertSame(
                ["id" => "def", "text" => "abc"],
                $this->requestFor(self::SLUG_URI . "abc-def")->getReadableParameter(0),
            );
        }

        public function testGetReadableParameter0SingleSegmentTheValueBecomesIdTextEmpty(): void {
            $this->assertSame(
                ["id" => "standalone", "text" => ""],
                $this->requestFor(self::SLUG_URI . "standalone")->getReadableParameter(0),
            );
        }

        public function testGetReadableParameter0ATrailingDashLeavesAnEmptyId(): void {
            $this->assertSame(
                ["id" => "", "text" => "abc"],
                $this->requestFor(self::SLUG_URI . "abc-")->getReadableParameter(0),
            );
        }

        public function testGetReadableParameter0AMissingSegmentGivesEmptyStrings(): void {
            $this->assertSame(["id" => "", "text" => ""], $this->requestFor("/Core/readable")->getReadableParameter(0));
        }

        public function testGetReadableParameter1OffsetSkipsOneParameterBeforeSplitting(): void {
            $this->assertSame(
                ["id" => "7", "text" => "foo-bar"],
                $this->requestFor(self::SLUG_URI . "skip-me/foo-bar-7")->getReadableParameter(1),
            );
        }

        public function testGetParametersSkipsControllerAndActionAndTheOffset(): void {
            $request = $this->requestFor("/Core/action/a/b/c");

            $this->assertSame(["a", "b", "c"], $request->getParameters());
            $this->assertSame(["b", "c"], $request->getParameters(1));
            $this->assertSame(["a", "b"], $request->getParameters(0, 2));
        }

        public function testGetParametersWithALengthOfOneReturnsTheParameterOrFalse(): void {
            $request = $this->requestFor("/Core/action/a");

            $this->assertSame("a", $request->getParameters(0, 1));
            $this->assertTrue($request->getParameters(0, 1, "a"));
            $this->assertFalse($request->getParameters(0, 1, "b"));
            $this->assertFalse($request->getParameters(1, 1));
        }

        // getJson()
        // Anything but a well formed application/json body throws an UnexpectedValueException
        // carrying the HTTP status as its code, malformed JSON throws a JsonException

        public function testRoundTripsAJsonObjectNextToTheRawBody(): void {
            $payload = ["hello" => "world", "n" => 42, "nested" => ["ok" => true]];
            $request = $this->jsonRequest(json_encode($payload));

            $this->assertSame(json_encode($payload), $request->getBody());
            $this->assertSame($payload, $request->getJson());
        }

        public function testAcceptsAUtf8CharsetInAnyCaseAndSpelling(): void {
            $request = $this->jsonRequest('{"a":1}', contentType: 'Application/JSON; Charset="UTF-8"');

            $this->assertSame(["a" => 1], $request->getJson());
        }

        public function testAcceptsAnyNumberOfOtherParametersNextToTheCharset(): void {
            $request = $this->jsonRequest('{"a":1}', contentType: "application/json; charset=utf-8; foo=bar");

            $this->assertSame(["a" => 1], $request->getJson());
        }

        public function testAcceptsAContentTypeWithoutACharset(): void {
            $request = $this->jsonRequest('{"a":1}', contentType: "application/json; foo=bar");

            $this->assertSame(["a" => 1], $request->getJson());
        }

        /** @dataProvider methodsWithABody */
        public function testAcceptsTheOtherMethodsThatCarryABody(string $method): void {
            $this->assertSame(["a" => 1], $this->jsonRequest('{"a":1}', $method)->getJson());
        }

        public static function methodsWithABody(): array {
            return [
                "PUT" => ["PUT"],
                "PATCH" => ["PATCH"],
                "DELETE" => ["DELETE"],
                "QUERY" => ["QUERY"],
                "a method in lower case" => ["post"],
            ];
        }

        public function testReadsTheContentTypeFromTheHttpPrefixedVariableToo(): void {
            $input = $this->input();
            $input->SERVER["REQUEST_METHOD"] = "POST";
            $input->SERVER["HTTP_CONTENT_TYPE"] = "application/json";
            $input->body = '{"a":1}';

            $this->assertSame(["a" => 1], $this->request($input)->getJson());
        }

        public function testKeepsIntegersBeyondPhpIntMaxAsStrings(): void {
            $request = $this->jsonRequest('{"big":123456789012345678901234567890}');

            $this->assertSame(["big" => "123456789012345678901234567890"], $request->getJson());
        }

        /** @dataProvider methodsWithoutABody */
        public function testRejectsAMethodWithoutABodyWith405(?string $method): void {
            $input = $this->input();
            $input->SERVER["CONTENT_TYPE"] = "application/json";
            $input->body = '{"a":1}';
            if(null !== $method) $input->SERVER["REQUEST_METHOD"] = $method;

            $this->assertRejected(405, self::ERROR_METHOD, $this->request($input));
        }

        public static function methodsWithoutABody(): array {
            return [
                "GET" => ["GET"],
                "HEAD" => ["HEAD"],
                "OPTIONS" => ["OPTIONS"],
                "no method at all" => [null],
            ];
        }

        /** @dataProvider otherContentTypes */
        public function testRejectsEveryOtherContentTypeWith415(string $contentType): void {
            $request = $this->jsonRequest('{"a":1}', contentType: $contentType);

            $this->assertRejected(415, self::ERROR_CONTENT_TYPE, $request);
        }

        public static function otherContentTypes(): array {
            return [
                "text/plain" => ["text/plain"],
                "application/x-www-form-urlencoded" => ["application/x-www-form-urlencoded"],
                "application/jsonp" => ["application/jsonp"],
                "application/vnd.api+json" => ["application/vnd.api+json"],
                "no content type at all" => [""],
            ];
        }

        /** @dataProvider otherCharsets */
        public function testRejectsACharsetOtherThanUtf8With415(string $contentType): void {
            $request = $this->jsonRequest('{"a":1}', contentType: $contentType);

            $this->assertRejected(415, self::ERROR_CHARSET, $request);
        }

        public static function otherCharsets(): array {
            return [
                "iso-8859-1" => ["application/json; charset=iso-8859-1"],
                "a name that only starts with utf-8" => ["application/json; charset=utf-8x"],
            ];
        }

        public function testRejectsABodyBeyondMaxBytesWith413(): void {
            $request = $this->jsonRequest('{"a":"0123456789"}');

            $this->assertRejected(413, self::ERROR_TOO_LARGE, $request, maxBytes: 10);
        }

        public function testAcceptsABodyOfExactlyMaxBytes(): void {
            $request = $this->jsonRequest('{"a":"01"}');

            $this->assertSame(["a" => "01"], $request->getJson(maxBytes: 10));
        }

        public function testRejectsABodyOneByteBeyondMaxBytes(): void {
            $request = $this->jsonRequest('{"a":"012"}');

            $this->assertRejected(413, self::ERROR_TOO_LARGE, $request, maxBytes: 10);
        }

        public function testCountsTheBodyInBytesNotCharacters(): void {
            // 9 characters, 10 bytes
            $request = $this->jsonRequest('{"a":"ä"}');

            $this->assertRejected(413, self::ERROR_TOO_LARGE, $request, maxBytes: 9);
            $this->assertSame(["a" => "ä"], $request->getJson(maxBytes: 10));
        }

        public function testRejectsALeadingBomWith400(): void {
            $request = $this->jsonRequest("\xEF\xBB\xBF{\"a\":1}");

            $this->assertMalformed(JSON_ERROR_SYNTAX, self::ERROR_SYNTAX, $request);
        }

        public function testReturnsATopLevelListAsArray(): void {
            $this->assertSame([1, 2], $this->jsonRequest("[1,2]")->getJson());
        }

        public function testRejectsMalformedJsonWith400(): void {
            $request = $this->jsonRequest('{"a":');

            $this->assertSame('{"a":', $request->getBody());
            $this->assertMalformed(JSON_ERROR_SYNTAX, self::ERROR_SYNTAX, $request);
        }

        public function testRejectsInvalidUtf8With400(): void {
            $request = $this->jsonRequest("{\"a\":\"\xB1\"}");

            $this->assertMalformed(JSON_ERROR_UTF8, "Malformed UTF-8 characters, possibly incorrectly encoded", $request);
        }

        public function testRejectsAnUnescapedControlCharacterWith400(): void {
            $request = $this->jsonRequest("{\"a\":\"line\nbreak\"}");

            $this->assertMalformed(JSON_ERROR_CTRL_CHAR, "Control character error, possibly incorrectly encoded", $request);
        }

        // A body without content reaches the request as null, never as an empty string
        public function testRejectsAnEmptyBodyAsMalformedWith400(): void {
            $request = $this->jsonRequest(null);

            $this->assertMalformed(JSON_ERROR_SYNTAX, self::ERROR_SYNTAX, $request);
        }

        public function testRejectsNestingBeyondMaxDepthWith400(): void {
            $request = $this->jsonRequest(str_repeat('{"a":', 40) . "1" . str_repeat("}", 40));

            $this->assertMalformed(JSON_ERROR_DEPTH, "Maximum stack depth exceeded", $request);
        }

        public function testAcceptsNestingUpToMaxDepth(): void {
            // PHP counts the scalar inside the two arrays as a level
            $request = $this->jsonRequest("[[1]]");

            $this->assertSame([[1]], $request->getJson(maxDepth: 3));
            $this->assertMalformed(JSON_ERROR_DEPTH, "Maximum stack depth exceeded", $request, maxDepth: 2);
        }

        public function testAcceptsNestingOfThirtyLevelsWithTheDefaultMaxDepth(): void {
            $request = $this->jsonRequest(str_repeat('{"a":', 30) . "1" . str_repeat("}", 30));

            $this->assertIsArray($request->getJson());
        }

        public function testGetBodyIsNullWithoutABody(): void {
            $this->assertNull($this->request()->getBody());
        }

        // getUrlParts() strips the segments of config("rootDirectory") off the parsed REQUEST_URI

        public function testGetUrlPartsReturnsTheParsedPathSegmentsVerbatimByDefault(): void {
            $this->assertSame(
                ["Routing", "urlParts", "x", "y"],
                $this->requestFor("/Routing/urlParts/x/y")->getUrlParts(),
            );
        }

        public function testGetUrlPartsStripsOneRootDirectorySegment(): void {
            $this->setSettings(["rootDirectory" => "Routing"]);

            $this->assertSame(
                ["urlParts_strip1", "x", "y"],
                $this->requestFor("/Routing/urlParts_strip1/x/y")->getUrlParts(),
            );
        }

        // count(explode("/", rootDirectory)) drives the strip count, so a
        // two-segment rootDirectory removes both leading parts
        public function testGetUrlPartsStripsTwoRootDirectorySegmentsMultiFolder(): void {
            $this->setSettings(["rootDirectory" => "Routing/urlParts_strip2"]);

            $this->assertSame(["x", "y"], $this->requestFor("/Routing/urlParts_strip2/x/y")->getUrlParts());
        }

        // "rootDirectory = sub/" was the only way to get working links before
        // v1.2.0, so the slashes must not count as extra segments
        public function testGetUrlPartsIgnoresSlashesAroundTheRootDirectory(): void {
            $this->setSettings(["rootDirectory" => "/Routing/"]);

            $this->assertSame(
                ["urlParts_stripSlashes", "x", "y"],
                $this->requestFor("/Routing/urlParts_stripSlashes/x/y")->getUrlParts(),
            );
        }

        // Built by app code that appends "/x" to a root ending with a slash
        public function testGetUrlPartsSkipsADoubledSlashAfterTheRootDirectory(): void {
            $this->setSettings(["rootDirectory" => "Routing/urlParts_strip2"]);

            $this->assertSame(["x", "y"], $this->requestFor("/Routing/urlParts_strip2//x/y")->getUrlParts());
        }

        /** @dataProvider urisWithoutSegments */
        public function testGetUrlPartsOfAnUriWithoutSegmentsIsEmpty(string $uri): void {
            $this->assertSame([], $this->requestFor($uri)->getUrlParts());
        }

        public static function urisWithoutSegments(): array {
            return [
                "the start page" => ["/"],
                "the start page with a query" => ["/?a=b"],
                "an empty uri" => [""],
            ];
        }

        public function testGetUrlPartsDoesNotDecodeSegmentsAndKeepsASlashInsideAQuery(): void {
            $this->assertSame(["a%20b", "c"], $this->requestFor("/a%20b/c?next=/x/y")->getUrlParts());
        }

        public function testGetUrlPartsKeepsWhatWasSetBeforehandAndCachesTheFirstResult(): void {
            $request = $this->requestFor("/a/b");
            $request->urlParts = ["preset"];
            $this->assertSame(["preset"], $request->getUrlParts());

            $request = $this->requestFor("/a/b");
            $this->assertSame(["a", "b"], $request->getUrlParts());
            $request->input->SERVER["REQUEST_URI"] = "/c/d";
            $this->assertSame(["a", "b"], $request->getUrlParts());
        }

        // checkPermission("console")
        // On the console the permission is granted. Elsewhere it is denied: with boolResult
        // as false, otherwise by rendering the 403 page and exiting

        public function testCliDispatchConstructGateAllowsTheActionToRun(): void {
            $request = $this->request();

            $this->assertTrue($request->isCli());
            $this->assertTrue($request->checkPermission("console"));
        }

        public function testCliDispatchAllowsTheConsolePermissionWithBoolResultAndSuperUserFlagsToo(): void {
            $request = $this->request();

            $this->assertTrue($request->checkPermission("console", boolResult: true));
            $this->assertTrue($request->checkPermission("console", includeSuperUser: true));
            $this->assertTrue($request->checkSuperPermission("console"));
        }

        public function testHttpDispatchWithBoolResultTrueReturnsFalseWithoutExiting(): void {
            $request = $this->httpRequest();

            $this->assertFalse($request->isCli());
            $this->assertFalse($request->checkPermission("console", boolResult: true));
        }

        public function testHttpDispatchWithBoolResultTrueDeniesTheSuperUserToo(): void {
            $request = $this->httpRequest();

            $this->assertFalse($request->checkPermission("console", boolResult: true, includeSuperUser: true));
            $this->assertFalse($request->checkSuperPermission("console", boolResult: true));
        }

        // hasFormData() matching rules

        public function testMatchesAnyFormSubmissionWhenCalledWithoutAnAction(): void {
            $this->assertSame(["any" => true, "named" => false], $this->formDataMatches(["isFormData" => 1]));
        }

        public function testMatchesANamedActionOnlyOnTheExactFormAction(): void {
            $this->assertSame(
                ["any" => true, "named" => true],
                $this->formDataMatches(["isFormData" => 1, "formAction" => self::FORM_ACTION]),
            );
        }

        public function testDoesNotMatchANamedActionOnADifferentFormAction(): void {
            $this->assertSame(
                ["any" => true, "named" => false],
                $this->formDataMatches(["isFormData" => 1, "formAction" => "other-action"]),
            );
        }

        public function testIsNoFormDataWithoutTheIsFormDataFlagEvenWithAFormAction(): void {
            $this->assertSame(
                ["any" => false, "named" => false],
                $this->formDataMatches(["formAction" => self::FORM_ACTION]),
            );
        }

        /** @dataProvider formActionsThatDifferOnlyLoosely */
        public function testComparesTheFormActionStrictly(string $posted, string $expected): void {
            $request = $this->request($this->input()->withPost(["isFormData" => 1, "formAction" => $posted]));

            $this->assertFalse($request->hasFormData($expected));
        }

        public static function formActionsThatDifferOnlyLoosely(): array {
            return [
                "numeric strings" => ["01", "1"],
                "different case" => ["Target-Action", self::FORM_ACTION],
            ];
        }

        /** @dataProvider formActionsThatLookEmpty */
        public function testMatchesAFormActionThatLooksEmpty(string $action): void {
            $request = $this->request($this->input()->withPost(["isFormData" => 1, "formAction" => $action]));

            $this->assertTrue($request->hasFormData($action));
        }

        public static function formActionsThatLookEmpty(): array {
            return [
                "zero" => ["0"],
                "empty string" => [""],
            ];
        }

        public function testDoesNotMatchANamedActionWhenNoFormActionWasPosted(): void {
            $request = $this->request($this->input()->withPost(["isFormData" => 1]));

            $this->assertFalse($request->hasFormData(""));
            $this->assertFalse($request->hasFormData("0"));
        }

        public function testDoesNotMatchAListOfFormActions(): void {
            $request = $this->request($this->input()->withPost([
                "isFormData" => 1,
                "formAction" => [self::FORM_ACTION],
            ]));

            $this->assertFalse($request->hasFormData(self::FORM_ACTION));
        }

        private function requestFor(string $uri): Request {
            $input = $this->input();
            $input->SERVER["REQUEST_URI"] = $uri;

            return $this->request($input);
        }

        private function requestStartedAgo(float $seconds): Request {
            $input = $this->input();
            $input->SERVER["REQUEST_TIME_FLOAT"] = microtime(true) - $seconds;

            return $this->request($input);
        }

        private function jsonRequest(?string $body, string $method = "POST", string $contentType = "application/json"): Request {
            $input = $this->input();
            $input->SERVER["REQUEST_METHOD"] = $method;
            $input->SERVER["CONTENT_TYPE"] = $contentType;
            $input->body = $body;

            return $this->request($input);
        }

        // A request that was not made from a console. isCli() is the seam, the
        // function behind it reads the PHP SAPI
        private function httpRequest(): Request {
            return new class($this->input()) extends Request {
                public function isCli(): bool {
                    return false;
                }
            };
        }

        private function assertRejected(int $status, string $message, Request $request, int $maxBytes = 1_048_576): void {
            $thrown = $this->assertThrows(UnexpectedValueException::class, $message, fn() => $request->getJson($maxBytes));

            $this->assertSame($status, $thrown->getCode());
        }

        private function assertMalformed(int $jsonError, string $message, Request $request, int $maxDepth = 32): void {
            $thrown = $this->assertThrows(JsonException::class, $message, fn() => $request->getJson(maxDepth: $maxDepth));

            $this->assertSame($jsonError, $thrown->getCode());
        }

        /** @param array<string, mixed> $post */
        private function formDataMatches(array $post): array {
            $request = $this->request($this->input()->withPost($post));

            return [
                "any" => $request->hasFormData(),
                "named" => $request->hasFormData(self::FORM_ACTION),
            ];
        }
    }

?>
