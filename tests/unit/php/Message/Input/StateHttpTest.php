<?php

    namespace Tests\Message\Input;

    use PHPUnit\Framework\TestCase;

    /**
     * State::fromRequest() against real HTTP requests. PHP's built-in web server
     * runs fixtures/state-server.php, which answers with PHP's own view of the
     * request next to the state, so the two can be compared for whatever a
     * client sends.
     */
    class StateHttpTest extends TestCase {

        private const DECODE_PREFIX = "<#decURI#>";

        // request_order as php.ini-production ships it: the cookies stay out of $_REQUEST
        private const PRODUCTION = ["request_order=GP", "variables_order=GPCS"];

        // No request_order at all, as without a php.ini: PHP falls back to variables_order, the cookies win
        private const FALLBACK = ["variables_order=EGPCS"];

        // A request_order that is set to nothing names no source, $_REQUEST stays empty
        private const NO_SOURCE = ["request_order=", "variables_order=EGPCS"];

        /** @var resource[] */
        private static array $servers = [];

        /** @var array<string, string> */
        private static array $addresses = [];

        public static function setUpBeforeClass(): void {
            self::startServer("production", self::PRODUCTION);
            self::startServer("fallback", self::FALLBACK);
            self::startServer("no source", self::NO_SOURCE);
        }

        public static function tearDownAfterClass(): void {
            foreach(self::$servers as $server) {
                proc_terminate($server);
                proc_close($server);
            }
            self::$servers = [];
            self::$addresses = [];
        }

        protected function setUp(): void {
            if(3 !== count(self::$addresses)) $this->markTestSkipped("PHP's built-in web server did not start.");
        }

        /** @param string[] $settings php.ini settings of the server */
        private static function startServer(string $name, array $settings): void {
            // The system hands out a free port, which is then given to the server
            $socket = stream_socket_server("tcp://127.0.0.1:0");
            $address = stream_socket_get_name($socket, false);
            fclose($socket);

            // Without the php.ini of the machine, so only the given settings count
            $command = [PHP_BINARY, "-n"];
            foreach($settings as $setting) {
                array_push($command, "-d", $setting);
            }
            array_push($command, "-S", $address, __DIR__ . "/fixtures/state-server.php");

            // The script of the server runs without the bootstrap of the tests, so it is told where the framework is
            self::$servers[] = proc_open(
                $command,
                [["file", "/dev/null", "r"], ["file", "/dev/null", "w"], ["file", "/dev/null", "w"]],
                $pipes,
                null,
                ["FRAMEWORK_ROOT" => FRAMEWORK_ROOT] + getenv(),
            );

            // Started once it accepts a connection
            for($attempt = 0; $attempt < 100; $attempt++) {
                $connection = @stream_socket_client("tcp://$address", $code, $message, 0.1);
                if(false === $connection) {
                    usleep(50_000);
                    continue;
                }

                fclose($connection);
                self::$addresses[$name] = $address;
                return;
            }
        }

        // The query

        /** @dataProvider queries */
        public function testTheStateHoldsTheQueryLikePhpDoes(string $query): void {
            $answer = $this->send("GET", "/some/path?$query");

            $this->assertSame($answer["globals"]["GET"], $answer["state"]["GET"]);
            $this->assertSame($answer["globals"]["REQUEST"], $answer["state"]["REQUEST"]);
            $this->assertSame($query, $answer["state"]["SERVER"]["QUERY_STRING"]);
            $this->assertSame("/some/path?$query", $answer["state"]["SERVER"]["REQUEST_URI"]);
        }

        // withUrl() with the uri of a request has to arrive at the very same request
        /** @dataProvider queries */
        public function testAStateBuiltFromTheUriOfARequestEqualsTheRequest(string $query): void {
            $answer = $this->send("GET", "/some/path?$query");

            $this->assertSame($answer["state"]["GET"], $answer["rebuilt"]["GET"]);
            $this->assertSame($answer["state"]["REQUEST"], $answer["rebuilt"]["REQUEST"]);
            $this->assertSame($answer["state"]["SERVER"]["QUERY_STRING"], $answer["rebuilt"]["SERVER"]["QUERY_STRING"]);
            $this->assertSame($answer["state"]["SERVER"]["REQUEST_URI"], $answer["rebuilt"]["SERVER"]["REQUEST_URI"]);
        }

        public static function queries(): array {
            return [
                "plain pairs" => ["a=1&b=two"],
                "a percent encoded space" => ["q=a%20b"],
                "a plus as a space" => ["q=a+b"],
                "list brackets" => ["tags[]=x&tags[]=y"],
                "named brackets" => ["f[a][b]=1&f[a][c]=2"],
                "a key without a value" => ["flag"],
                "a repeated key" => ["a=1&a=2"],
                "a dot in a key" => ["a.b=1"],
                "numeric keys" => ["1=a&7=b"],
                "an encoded ampersand" => ["q=a%26b%3Dc"],
                "utf-8" => ["q=%C3%A4%C3%B6"],
                "a zero" => ["0"],
            ];
        }

        public function testARequestWithoutAQueryHasAnEmptyGet(): void {
            $answer = $this->send("GET", "/some/path");

            $this->assertSame([], $answer["state"]["GET"]);
            $this->assertSame("/some/path", $answer["state"]["SERVER"]["REQUEST_URI"]);
            $this->assertNull($answer["state"]["body"]);
        }

        // A form

        public function testTheStateHoldsAPostedFormLikePhpDoes(): void {
            $answer = $this->sendForm("/form?page=2", "name=value&list[]=a&list[]=b&nested[deep]=x&empty=&1=numeric");

            $this->assertSame($answer["globals"]["POST"], $answer["state"]["POST"]);
            $this->assertSame($answer["globals"]["GET"], $answer["state"]["GET"]);
            $this->assertSame($answer["globals"]["REQUEST"], $answer["state"]["REQUEST"]);
            $this->assertSame("POST", $answer["state"]["SERVER"]["REQUEST_METHOD"]);
        }

        public function testThePostWinsOverTheQueryInTheRequestAlsoForNumericKeys(): void {
            $answer = $this->sendForm("/form?1=from-get&only=get", "1=from-post");

            $this->assertSame([1 => "from-post", "only" => "get"], $answer["state"]["REQUEST"]);
            $this->assertSame($answer["globals"]["REQUEST"], $answer["state"]["REQUEST"]);
        }

        public function testAPrefixedValueIsDecodedInThePostAndInTheRequest(): void {
            $prefix = rawurlencode(self::DECODE_PREFIX);
            $answer = $this->sendForm("/form?q=kept", "text={$prefix}a%2520b%2526c&nested[deep]={$prefix}%25C3%25A4&plain=a%2520b");

            $expected = ["text" => "a b&c", "nested" => ["deep" => "ä"], "plain" => "a%20b"];
            $this->assertSame($expected, $answer["state"]["POST"]);
            $this->assertSame(["q" => "kept"] + $expected, $answer["state"]["REQUEST"]);
            // Documented: the decoded value replaces the one in $_POST itself
            $this->assertSame($expected, $answer["postAfterwards"]);
        }

        // The body

        public function testTheBodyOfAFormIsTheFormAsItWasSent(): void {
            $answer = $this->sendForm("/form", "name=value&a%20b=c");

            $this->assertSame("name=value&a%20b=c", $answer["state"]["body"]);
        }

        /** @dataProvider bodies */
        public function testTheBodyArrivesUnchanged(string $body): void {
            $answer = $this->send("POST", "/api", ["Content-Type: application/json"], $body);

            $this->assertSame($body, $answer["state"]["body"]);
            $this->assertSame([], $answer["state"]["POST"]);
        }

        public static function bodies(): array {
            return [
                "a JSON object" => ['{"a":1,"b":[true,null]}'],
                "a single space" => [" "],
                // PHP takes the string "0" for false
                "a zero" => ["0"],
                "the word false" => ["false"],
                "utf-8" => ['{"name":"äöü"}'],
                "a line break" => ["line one\nline two"],
            ];
        }

        public function testAnEmptyBodyIsNull(): void {
            $answer = $this->send("POST", "/api", ["Content-Type: application/json"], "");

            $this->assertNull($answer["state"]["body"]);
        }

        /** @dataProvider methodsWithABody */
        public function testTheBodyOfEveryMethodIsRead(string $method): void {
            $answer = $this->send($method, "/api/5", ["Content-Type: application/json"], '{"a":1}');

            $this->assertSame($method, $answer["state"]["SERVER"]["REQUEST_METHOD"]);
            $this->assertSame('{"a":1}', $answer["state"]["body"]);
        }

        public static function methodsWithABody(): array {
            return [
                "PUT" => ["PUT"],
                "PATCH" => ["PATCH"],
                "DELETE" => ["DELETE"],
            ];
        }

        // Cookies

        public function testTheStateHoldsTheCookiesLikePhpDoes(): void {
            $answer = $this->send("GET", "/", ["Cookie: z_login_token=abc; theme=dark; a.b=dot; list[x]=1"]);

            $this->assertSame($answer["globals"]["COOKIE"], $answer["state"]["COOKIE"]);
            $this->assertSame("abc", $answer["state"]["COOKIE"]["z_login_token"]);
        }

        // REQUEST

        // With request_order=GP PHP keeps the cookies out of $_REQUEST
        public function testTheRequestFollowsTheRequestOrderOfPhp(): void {
            $answer = $this->sendForm("/form?key=get", "key=post", ["Cookie: key=cookie; only=cookie"]);

            $this->assertSame(["key" => "post"], $answer["globals"]["REQUEST"]);
            $this->assertSame($answer["globals"]["REQUEST"], $answer["state"]["REQUEST"]);
        }

        // Without a request_order PHP falls back to variables_order, where the cookies come last and win
        public function testTheRequestFollowsTheVariablesOrderWithoutARequestOrder(): void {
            $answer = $this->sendForm("/form?key=get", "key=post", ["Cookie: key=cookie; only=cookie"], "fallback");

            $this->assertSame(["key" => "cookie", "only" => "cookie"], $answer["globals"]["REQUEST"]);
            $this->assertSame($answer["globals"]["REQUEST"], $answer["state"]["REQUEST"]);
        }

        public function testTheRequestIsEmptyForARequestOrderThatIsSetToNothing(): void {
            $answer = $this->sendForm("/form?key=get", "key=post", ["Cookie: key=cookie"], "no source");

            $this->assertSame([], $answer["globals"]["REQUEST"]);
            $this->assertSame([], $answer["state"]["REQUEST"]);
        }

        // A state that puts REQUEST together itself has to arrive at what PHP made of the same request
        /** @dataProvider servers */
        public function testARequestThatIsPutTogetherAgainEqualsTheOneOfPhp(string $server): void {
            $answer = $this->sendForm("/form?key=get&g=1&5=get", "key=post&p=1&5=post", ["Cookie: key=cookie; c=1"], $server);

            $this->assertSame($answer["globals"]["REQUEST"], $answer["rebuilt"]["REQUEST"]);
        }

        public static function servers(): array {
            return [
                "request_order=GP" => ["production"],
                "variables_order=EGPCS" => ["fallback"],
                "request_order set to nothing" => ["no source"],
            ];
        }

        // Headers

        public function testTheAuthorizationHeaderReachesTheState(): void {
            $answer = $this->send("GET", "/", ["Authorization: Bearer token-123"]);

            $this->assertSame("Bearer token-123", $answer["state"]["SERVER"]["HTTP_AUTHORIZATION"]);
        }

        public function testTheHeadersOfTheRequestReachTheState(): void {
            $answer = $this->send("GET", "/", [
                "User-Agent: Unit Test",
                "Accept-Language: de-CH,de;q=0.9",
                "Referer: http://referer.example/page",
                "X-Forwarded-For: 203.0.113.7",
            ]);

            $server = $answer["state"]["SERVER"];
            $this->assertSame("Unit Test", $server["HTTP_USER_AGENT"]);
            $this->assertSame("de-CH,de;q=0.9", $server["HTTP_ACCEPT_LANGUAGE"]);
            $this->assertSame("http://referer.example/page", $server["HTTP_REFERER"]);
            $this->assertSame("203.0.113.7", $server["HTTP_X_FORWARDED_FOR"]);
            $this->assertSame(self::$addresses["production"], $server["HTTP_HOST"]);
            $this->assertSame("127.0.0.1", $server["REMOTE_ADDR"]);
        }

        // An upload

        public function testTheStateHoldsAnUploadLikePhpDoes(): void {
            $boundary = "----boundary" . bin2hex(random_bytes(8));
            $body = "--$boundary\r\n"
                . "Content-Disposition: form-data; name=\"title\"\r\n\r\n"
                . self::DECODE_PREFIX . "a%20b\r\n"
                . "--$boundary\r\n"
                . "Content-Disposition: form-data; name=\"upload\"; filename=\"note.txt\"\r\n"
                . "Content-Type: text/plain\r\n\r\n"
                . "file content\r\n"
                . "--$boundary--\r\n";

            $answer = $this->send("POST", "/upload", ["Content-Type: multipart/form-data; boundary=$boundary"], $body);

            $this->assertSame($answer["globals"]["FILES"], $answer["state"]["FILES"]);
            $this->assertSame("note.txt", $answer["state"]["FILES"]["upload"]["name"]);
            $this->assertSame(12, $answer["state"]["FILES"]["upload"]["size"]);
            $this->assertSame(UPLOAD_ERR_OK, $answer["state"]["FILES"]["upload"]["error"]);
            $this->assertSame(["title" => "a b"], $answer["state"]["POST"]);
            // PHP has consumed a multipart body, there is none left to read
            $this->assertNull($answer["state"]["body"]);
        }

        /** @param string[] $headers */
        private function sendForm(string $uri, string $form, array $headers = [], string $server = "production"): array {
            return $this->send("POST", $uri, ["Content-Type: application/x-www-form-urlencoded", ...$headers], $form, $server);
        }

        /**
         * @param string[] $headers
         * @return array<string, mixed> What the server answered
         */
        private function send(string $method, string $uri, array $headers = [], ?string $body = null, string $server = "production"): array {
            $answer = file_get_contents("http://" . self::$addresses[$server] . $uri, false, stream_context_create(["http" => [
                "method" => $method,
                "header" => implode("\r\n", $headers),
                "content" => $body,
                "ignore_errors" => true,
                "timeout" => 10,
            ]]));

            return json_decode($answer, true, 512, JSON_THROW_ON_ERROR);
        }
    }

?>
