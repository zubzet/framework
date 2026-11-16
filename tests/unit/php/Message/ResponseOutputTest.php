<?php

    namespace Tests\Message;

    use JsonException;
    use Tests\TestCase;

    /**
     * What the response prints. json() and the cookie functions send headers,
     * which PHP refuses once the test runner has printed anything, so every
     * test runs in a process of its own. The headers themselves are not
     * visible on the command line, the e2e suite asserts them.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    class ResponseOutputTest extends TestCase {

        protected function setUp(): void {
            $this->bootFramework(["host" => "http://localhost:8080", "rootDirectory" => ""]);
        }

        // json()

        /** @dataProvider jsonValues */
        public function testJsonPrintsTheEncodedValue(mixed $value, string $expected): void {
            $this->assertSame($expected, $this->printed(fn() => $this->response()->json($value)));
        }

        public static function jsonValues(): array {
            return [
                "an object" => [["a" => 1, "b" => [true, null]], '{"a":1,"b":[true,null]}'],
                "a list" => [[1, 2], "[1,2]"],
                "an empty array is a list" => [[], "[]"],
                "a string" => ["text", '"text"'],
                "null" => [null, "null"],
                "a zero" => [0, "0"],
                "false" => [false, "false"],
            ];
        }

        public function testJsonPassesTheEncodingOptionsOn(): void {
            $printed = $this->printed(fn() => $this->response()->json(["a" => "ä/b"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $this->assertSame("{\n    \"a\": \"ä/b\"\n}", $printed);
        }

        public function testJsonThrowsForAValueThatCannotBeEncodedAndPrintsNothing(): void {
            $printed = null;

            try {
                $printed = $this->printed(fn() => $this->response()->json(["a" => "\xff"]));
                $this->fail("Expected a JsonException, but nothing was thrown.");
            } catch(JsonException $thrown) {
                $this->assertSame(JSON_ERROR_UTF8, $thrown->getCode());
            }

            $this->assertNull($printed);
        }

        // json() does not end the request, a second call adds to the first
        public function testJsonDoesNotEndTheRequest(): void {
            $printed = $this->printed(function() {
                $this->response()->json(["a" => 1]);
                echo "|after";
            });

            $this->assertSame('{"a":1}|after', $printed);
        }

        // generateRest()

        public function testGenerateRestNamesThePathOfTheCurrentRequest(): void {
            $this->request($this->input()->withUrl("/users/show/5?page=2"));

            $printed = $this->printed(fn() => $this->response()->generateRest(["result" => "success"], false));

            $reply = json_decode($printed, true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame("users/show/5", $reply["meta"]["request"]);
            $this->assertSame("success", $reply["result"]);
        }

        public function testGenerateRestNamesThePathBelowTheRootDirectory(): void {
            $this->setSettings(["rootDirectory" => "sub"]);
            $this->request($this->input()->withUrl("/sub/users/show"));

            $printed = $this->printed(fn() => $this->response()->generateRest([], false));

            $this->assertSame("users/show", json_decode($printed, true)["meta"]["request"]);
        }

        // PHP's default text/html would let markup inside a value run in a browser that opens the
        // endpoint. application/json makes jQuery parse the reply itself, which breaks code that
        // calls JSON.parse() on it, so only a client that asks for JSON gets that type.
        /** @dataProvider acceptHeaders */
        public function testARestReplyIsJsonForAClientThatAsksForItAndPlainTextOtherwise(?string $accept, string $expected): void {
            $input = $this->input();
            if(null !== $accept) $input->SERVER["HTTP_ACCEPT"] = $accept;
            $this->request($input);

            $this->assertSame($expected, $this->callHidden($this->response(), "restContentType"));
        }

        public static function acceptHeaders(): array {
            $json = "application/json";
            $text = "text/plain; charset=utf-8";

            return [
                "a plain \$.post() of jQuery" => ["*/*", $text],
                "jQuery with the dataType json" => ["application/json, text/javascript, */*; q=0.01", $json],
                "a client that asks for JSON only" => ["application/json", $json],
                "another case" => ["Application/JSON", $json],
                "a browser that opens the endpoint" => ["text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8", $text],
                "another JSON type" => ["application/problem+json", $text],
                "an empty header" => ["", $text],
                "no header" => [null, $text],
            ];
        }

        // Cookies

        /** @dataProvider hosts */
        public function testFrameworkCookiesAreSecureOnAnHttpsHost(mixed $host, bool $secure): void {
            $this->setSettings(["host" => $host]);

            $this->assertSame($secure, $this->response()->cookieSecure());
        }

        public static function hosts(): array {
            return [
                "https" => ["https://example.com", true],
                "http" => ["http://example.com", false],
                // A scheme is case insensitive
                "https in capitals" => ["HTTPS://example.com", true],
                "no scheme" => ["example.com", false],
                "https somewhere else in the host" => ["http://https://example.com", false],
                "no host" => [null, false],
            ];
        }

        // An action that removes a cookie must not read it again later in the same request
        public function testUnsetCookieRemovesTheCookieFromTheCurrentRequest(): void {
            $request = $this->request($this->input()->withCookies(["theme" => "dark", "keep" => "this"]));

            $this->printed(fn() => $this->response()->unsetCookie("theme"));

            $this->assertNull($request->getCookie("theme"));
            $this->assertSame(["keep" => "this"], $request->getCookies());
        }

        public function testUnsetCookieOfAnUnknownCookieChangesNothing(): void {
            $request = $this->request($this->input()->withCookies(["keep" => "this"]));

            $this->printed(fn() => $this->response()->unsetCookie("unknown"));

            $this->assertSame(["keep" => "this"], $request->getCookies());
        }

        // What the action prints
        private function printed(callable $action): string {
            ob_start();
            try {
                $action();
                return ob_get_contents();
            } finally {
                ob_end_clean();
            }
        }
    }

?>
