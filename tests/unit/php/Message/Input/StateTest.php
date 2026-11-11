<?php

    namespace Tests\Message\Input;

    use InvalidArgumentException;
    use LogicException;
    use Tests\TestCase;
    use ZubZet\Framework\Message\Input\State;

    class StateTest extends TestCase {

        private const ERROR_UNKNOWN_TYPE = "OverwriteData includes unknown type: 'NOT_A_FIELD'.";
        private const ERROR_NO_PREVIOUS = "Cannot set previous input as referer when there is no previous input.";
        private const ERROR_NO_HOST = "Cannot set previous input as referer when previous input does not have HTTP_HOST set.";
        private const ERROR_NO_SCHEME = "Cannot set previous input as referer when previous input does not have REQUEST_SCHEME set.";
        private const DECODE_PREFIX = "<#decURI#>";

        // fromOverwrite()

        public function testMergesPerKeyIntoTheExistingArrays(): void {
            $parent = $this->input();
            $parent->GET = ["a" => "1", "b" => "2"];

            $child = State::fromOverwrite($parent, [
                "GET" => ["b" => "overridden", "c" => "new"],
            ]);

            $this->assertSame(["a" => "1", "b" => "2"], $parent->GET, "The parent is left intact");
            $this->assertSame(["a" => "1", "b" => "overridden", "c" => "new"], $child->GET, "The child merges new keys and overwrites collisions");
        }

        public function testMergesSeveralTypesAtOnceAndLeavesTheOthersAlone(): void {
            $parent = $this->input();
            $parent->SERVER = ["HTTP_HOST" => "parent.example", "REQUEST_METHOD" => "GET"];
            $parent->COOKIE = ["session" => "abc"];

            $child = State::fromOverwrite($parent, [
                "SERVER" => ["HTTP_HOST" => "child.example"],
                "POST" => ["name" => "value"],
            ]);

            $this->assertSame(["HTTP_HOST" => "child.example", "REQUEST_METHOD" => "GET"], $child->SERVER);
            $this->assertSame(["name" => "value"], $child->POST);
            $this->assertSame(["session" => "abc"], $child->COOKIE);
            $this->assertSame("parent.example", $parent->SERVER["HTTP_HOST"]);
            $this->assertSame([], $parent->POST);
        }

        public function testChildPreviousTracksTheParentByReference(): void {
            $parent = $this->input();
            $parent->GET = ["a" => "original"];

            $child = State::fromOverwrite($parent, []);

            // Mutated after the call, the child has to see it through its back-pointer
            $parent->GET["a"] = "mutated-after";

            $this->assertSame($parent, $child->previous);
            $this->assertSame(["a" => "mutated-after"], $child->previous->GET);
            // The own GET of the child was copied when it was created
            $this->assertSame(["a" => "original"], $child->GET);
        }

        public function testAForkOfAForkPointsToTheStateItWasForkedFrom(): void {
            $parent = $this->input();
            $child = State::fromOverwrite($parent, []);
            $grandchild = State::fromOverwrite($child, []);

            $this->assertNull($parent->previous);
            $this->assertSame($parent, $child->previous);
            $this->assertSame($child, $grandchild->previous);
        }

        public function testRejectsUnknownOverwriteKeysWithInvalidArgumentException(): void {
            $this->assertThrows(InvalidArgumentException::class, self::ERROR_UNKNOWN_TYPE, function() {
                State::fromOverwrite($this->input(), ["NOT_A_FIELD" => ["k" => "v"]]);
            });
        }

        public function testParentIsUnaffectedByCloneMutations(): void {
            $parent = $this->input();
            $parent->POST = ["k" => "parent"];

            $child = State::fromOverwrite($parent, []);
            $child->withPost(["k" => "child"]);

            $this->assertSame(["k" => "parent"], $parent->POST);
            $this->assertSame(["k" => "child"], $child->POST);
        }

        // withUrl()

        public function testWiresSchemeHostPathQueryGetInOneCall(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "";
            $state->withUrl("https://example.com/foo/bar?x=1&y=two");

            $this->assertSame("https", $state->SERVER["REQUEST_SCHEME"]);
            $this->assertSame("on", $state->SERVER["HTTPS"]);
            $this->assertSame("example.com", $state->SERVER["HTTP_HOST"]);
            $this->assertSame("/foo/bar?x=1&y=two", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/foo/bar", $state->SERVER["REDIRECT_URL"]);
            $this->assertSame("x=1&y=two", $state->SERVER["QUERY_STRING"]);
            $this->assertSame(["x" => "1", "y" => "two"], $state->GET);
            $this->assertSame(["x" => "1", "y" => "two"], $state->REQUEST);
        }

        public function testHttpsSchemeSetsServerHttpsOn(): void {
            $state = $this->input()->withUrl("https://example.com/");

            $this->assertSame("https", $state->SERVER["REQUEST_SCHEME"]);
            $this->assertSame("on", $state->SERVER["HTTPS"]);
        }

        public function testHttpSchemeSetsServerHttpsOff(): void {
            $state = $this->input()->withUrl("http://example.com/");

            $this->assertSame("http", $state->SERVER["REQUEST_SCHEME"]);
            $this->assertSame("off", $state->SERVER["HTTPS"]);
        }

        // parse_url yields only the path for a string with a leading slash, so
        // scheme and host have to stay as they were
        public function testPathOnlyUrlLeavesPriorSchemeHostIntact(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_SCHEME"] = "preserved";
            $state->SERVER["HTTP_HOST"] = "preserved.example";
            $state->withUrl("/just/a/path");

            $this->assertSame("preserved", $state->SERVER["REQUEST_SCHEME"]);
            $this->assertSame("preserved.example", $state->SERVER["HTTP_HOST"]);
            $this->assertSame("/just/a/path", $state->SERVER["REQUEST_URI"]);
        }

        public function testQueryOnlyUrlReplacesQueryWhilePreservingPath(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/original";
            $state->withUrl("?fresh=value");

            $this->assertSame("/original?fresh=value", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("fresh=value", $state->SERVER["QUERY_STRING"]);
            $this->assertSame(["fresh" => "value"], $state->GET);
        }

        public function testAUrlWithAQueryWorksOnAnInputWithoutRequestUri(): void {
            $state = $this->input()->withUrl("https://example.com/foo/bar?x=1&y=two");

            $this->assertSame("/foo/bar?x=1&y=two", $state->SERVER["REQUEST_URI"]);
            $this->assertSame(["x" => "1", "y" => "two"], $state->GET);
        }

        public function testAUrlWithoutAQueryKeepsThePriorQuery(): void {
            $state = $this->input()->withGet(["a" => "1"]);
            $state->withUrl("/other");

            $this->assertSame("/other?a=1", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/other", $state->SERVER["REDIRECT_URL"]);
            $this->assertSame("a=1", $state->SERVER["QUERY_STRING"]);
            $this->assertSame(["a" => "1"], $state->GET);
        }

        public function testTheQueryOfAUrlIsDecodedIntoGet(): void {
            $state = $this->input()->withUrl("/search?q=a%20b%26c&tags[]=x&tags[]=y");

            $this->assertSame(["q" => "a b&c", "tags" => ["x", "y"]], $state->GET);
        }

        // withPath()

        public function testRewritesRequestUriAndRedirectUrl(): void {
            $state = $this->input()->withPath("/foo/bar");

            $this->assertSame("/foo/bar", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/foo/bar", $state->SERVER["REDIRECT_URL"]);
        }

        public function testStripsLeadingSlashesBeforeReformatting(): void {
            $state = $this->input()->withPath("///foo");

            $this->assertSame("/foo", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/foo", $state->SERVER["REDIRECT_URL"]);
        }

        // A path-only change keeps the prior query string on REQUEST_URI,
        // REDIRECT_URL never includes it
        public function testPreservesAnyPriorQueryStringOnRequestUri(): void {
            $state = $this->input();
            $state->SERVER["QUERY_STRING"] = "x=1&y=two";
            $state->withPath("/new-path");

            $this->assertSame("/new-path?x=1&y=two", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/new-path", $state->SERVER["REDIRECT_URL"]);
        }

        public function testOmitsTheQuerySeparatorWhenNoQueryStringIsSet(): void {
            $state = $this->input()->withPath("/new-path");

            $this->assertSame("/new-path", $state->SERVER["REQUEST_URI"]);
        }

        public function testOmitsTheQuerySeparatorForAnEmptyQueryString(): void {
            $state = $this->input();
            $state->SERVER["QUERY_STRING"] = "";
            $state->withPath("/new-path");

            $this->assertSame("/new-path", $state->SERVER["REQUEST_URI"]);
        }

        // withGet()

        public function testReplacesGetWholesale(): void {
            $state = $this->input();
            $state->GET = ["old" => "1"];
            $state->SERVER["REQUEST_URI"] = "/foo?old=1";
            $state->withGet(["only" => "new"]);

            $this->assertSame(["only" => "new"], $state->GET);
            $this->assertSame("only=new", $state->SERVER["QUERY_STRING"]);
            $this->assertSame("/foo?only=new", $state->SERVER["REQUEST_URI"]);
            $this->assertSame(["only" => "new"], $state->REQUEST);
        }

        public function testEmptyArrayClearsBothQueryStringAndRequestUriQuery(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/foo?x=1";
            $state->withGet([]);

            $this->assertSame("/foo", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("", $state->SERVER["QUERY_STRING"]);
            $this->assertSame([], $state->GET);
        }

        // The path is kept by cutting REQUEST_URI at the question mark
        public function testKeepsThePathWhileSwappingTheQuery(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/some/path?stale=yes";
            $state->withGet(["fresh" => "yes"]);

            $this->assertSame("/some/path?fresh=yes", $state->SERVER["REQUEST_URI"]);
        }

        // An input that never had a REQUEST_URI, like a command line one
        public function testSurvivesAStateWithoutPriorServerRequestUri(): void {
            $state = $this->input();
            $state->withGet(["fresh" => "value"]);

            $this->assertSame("fresh=value", $state->SERVER["QUERY_STRING"]);
            $this->assertSame(["fresh" => "value"], $state->GET);
        }

        public function testEncodesTheQueryStringAndKeepsGetAsGiven(): void {
            $get = ["q" => "a b&c", "list" => ["x", "y"]];
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/foo";
            $state->withGet($get);

            $this->assertSame("q=a+b%26c&list%5B0%5D=x&list%5B1%5D=y", $state->SERVER["QUERY_STRING"]);
            $this->assertSame("/foo?q=a+b%26c&list%5B0%5D=x&list%5B1%5D=y", $state->SERVER["REQUEST_URI"]);
            $this->assertSame($get, $state->GET);
        }

        // withPost() / withFiles() / withSession() / withBody() / withMethod() / withCookies() / withReferer()

        public function testWithPostReplacesPostAndRefreshesRequest(): void {
            $state = $this->input();
            $state->POST = ["old" => "x"];
            $state->REQUEST = ["old" => "x"];
            $state->withPost(["fresh" => "value"]);

            $this->assertSame(["fresh" => "value"], $state->POST);
            $this->assertSame(["fresh" => "value"], $state->REQUEST);
        }

        public function testWithFilesReplacesFilesWholesale(): void {
            $state = $this->input();
            $state->FILES = ["before" => ["name" => "before.txt"]];
            $state->withFiles(["after" => ["name" => "after.txt"]]);

            $this->assertSame(["after" => ["name" => "after.txt"]], $state->FILES);
        }

        public function testWithSessionReplacesSessionWholesale(): void {
            $state = $this->input();
            $state->SESSION = ["before" => 1];
            $state->withSession(["after" => 2]);

            $this->assertSame(["after" => 2], $state->SESSION);
        }

        public function testWithBodyOverwritesTheRawBodyString(): void {
            $state = $this->input();
            $state->body = "old body";
            $state->withBody("new body");

            $this->assertSame("new body", $state->body);
        }

        public function testWithMethodOverwritesServerRequestMethod(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_METHOD"] = "GET";
            $state->withMethod("DELETE");

            $this->assertSame("DELETE", $state->SERVER["REQUEST_METHOD"]);
        }

        public function testWithCookiesReplacesCookieAndRefreshesRequest(): void {
            $state = $this->input();
            $state->COOKIE = ["before" => "1"];
            $state->REQUEST = ["before" => "1"];
            $state->withCookies(["after" => "2"]);

            $this->assertSame(["after" => "2"], $state->COOKIE);
            $this->assertSame(["after" => "2"], $state->REQUEST);
        }

        public function testWithRefererOverwritesHttpRefererVerbatim(): void {
            $state = $this->input();
            $state->SERVER["HTTP_REFERER"] = "https://old.example/";
            $state->withReferer("https://prev.example/page");

            $this->assertSame("https://prev.example/page", $state->SERVER["HTTP_REFERER"]);
        }

        /** @dataProvider withers */
        public function testEveryWitherReturnsTheStateItWasCalledOn(callable $wither): void {
            $state = $this->input();

            $this->assertSame($state, $wither($state));
        }

        public static function withers(): array {
            return [
                "withUrl" => [fn(State $state) => $state->withUrl("https://example.com/a?b=1")],
                "withPath" => [fn(State $state) => $state->withPath("/a")],
                "withGet" => [fn(State $state) => $state->withGet(["a" => "1"])],
                "withBody" => [fn(State $state) => $state->withBody("body")],
                "withPost" => [fn(State $state) => $state->withPost(["a" => "1"])],
                "withFiles" => [fn(State $state) => $state->withFiles([])],
                "withSession" => [fn(State $state) => $state->withSession([])],
                "withCookies" => [fn(State $state) => $state->withCookies(["a" => "1"])],
                "withMethod" => [fn(State $state) => $state->withMethod("POST")],
                "withReferer" => [fn(State $state) => $state->withReferer("https://example.com/")],
                "withArgs" => [fn(State $state) => $state->withArgs(["a"])],
            ];
        }

        // updateRequest() merge precedence, observed through withGet, withPost and withCookies

        public function testRequestEqualsArrayMergeGetPostCookieCookieBeatsPostBeatsGet(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "";
            $state->withGet(["common" => "fromGet", "g" => "g"])
                ->withPost(["common" => "fromPost", "p" => "p"])
                ->withCookies(["common" => "fromCookie", "c" => "c"]);

            $this->assertSame([
                "common" => "fromCookie",
                "g" => "g",
                "p" => "p",
                "c" => "c",
            ], $state->REQUEST);
        }

        // withArgs()

        // argv[0] is the script, argv[1] the command; the rest is replaced
        public function testKeepsArgv01AndReplacesTheRest(): void {
            $state = $this->input();
            $state->SERVER["argv"] = ["index.php", "command", "stale1", "stale2"];
            $state->withArgs(["fresh1", "fresh2", "fresh3"]);

            $this->assertSame(["index.php", "command", "fresh1", "fresh2", "fresh3"], $state->SERVER["argv"]);
        }

        public function testSurvivesAMissingServerArgvTreatsItAsEmpty(): void {
            $state = $this->input();
            $state->withArgs(["a", "b"]);

            $this->assertSame(["a", "b"], $state->SERVER["argv"]);
        }

        public function testAnEmptyArgumentListLeavesOnlyScriptAndCommand(): void {
            $state = $this->input();
            $state->SERVER["argv"] = ["index.php", "command", "stale"];
            $state->withArgs([]);

            $this->assertSame(["index.php", "command"], $state->SERVER["argv"]);
        }

        // withPreviousAsReferer()

        public function testBuildsSchemeHostPathFromThePreviousState(): void {
            $child = $this->childOf([
                "REQUEST_SCHEME" => "https",
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/old/page?x=1",
            ]);
            $child->withPreviousAsReferer();

            $this->assertSame("https://prev.example/old/page?x=1", $child->SERVER["HTTP_REFERER"]);
        }

        public function testThrowsLogicExceptionWhenThereIsNoPreviousState(): void {
            $this->assertThrows(LogicException::class, self::ERROR_NO_PREVIOUS, function() {
                $this->input()->withPreviousAsReferer();
            });
        }

        public function testThrowsLogicExceptionWhenThePreviousHttpHostIsEmpty(): void {
            $child = $this->childOf([
                "REQUEST_SCHEME" => "https",
                "REQUEST_URI" => "/old/page",
            ]);

            $this->assertThrows(LogicException::class, self::ERROR_NO_HOST, fn() => $child->withPreviousAsReferer());
        }

        // The call refuses to produce a partial (path-only) referer
        public function testThrowsLogicExceptionWhenThePreviousRequestSchemeIsEmpty(): void {
            $child = $this->childOf([
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/old/page",
            ]);

            $this->assertThrows(LogicException::class, self::ERROR_NO_SCHEME, fn() => $child->withPreviousAsReferer());
        }

        public function testAnEmptyStringAsPreviousHttpHostIsRejectedLikeAMissingOne(): void {
            $child = $this->childOf([
                "REQUEST_SCHEME" => "https",
                "HTTP_HOST" => "",
                "REQUEST_URI" => "/old/page",
            ]);

            $this->assertThrows(LogicException::class, self::ERROR_NO_HOST, fn() => $child->withPreviousAsReferer());
        }

        public function testAnEmptyStringAsPreviousRequestSchemeIsRejectedLikeAMissingOne(): void {
            $child = $this->childOf([
                "REQUEST_SCHEME" => "",
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/old/page",
            ]);

            $this->assertThrows(LogicException::class, self::ERROR_NO_SCHEME, fn() => $child->withPreviousAsReferer());
        }

        public function testTheHostIsCheckedBeforeTheScheme(): void {
            $child = $this->childOf(["REQUEST_URI" => "/old/page"]);

            $this->assertThrows(LogicException::class, self::ERROR_NO_HOST, fn() => $child->withPreviousAsReferer());
        }

        public function testTheRefererHasNoPathWithoutAPreviousRequestUri(): void {
            $child = $this->childOf([
                "REQUEST_SCHEME" => "http",
                "HTTP_HOST" => "prev.example",
            ]);
            $child->withPreviousAsReferer();

            $this->assertSame("http://prev.example", $child->SERVER["HTTP_REFERER"]);
        }

        public function testThePreviousRootPathKeepsItsSlash(): void {
            $child = $this->childOf([
                "REQUEST_SCHEME" => "https",
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/",
            ]);
            $child->withPreviousAsReferer();

            $this->assertSame("https://prev.example/", $child->SERVER["HTTP_REFERER"]);
        }

        public function testTheRefererComesFromThePreviousStateAndNotFromTheCurrentOne(): void {
            $parent = $this->input();
            $parent->SERVER = [
                "REQUEST_SCHEME" => "https",
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/old/page",
            ];
            $child = State::fromOverwrite($parent, ["SERVER" => [
                "REQUEST_SCHEME" => "http",
                "HTTP_HOST" => "current.example",
                "REQUEST_URI" => "/current",
            ]]);
            $child->withPreviousAsReferer();

            $this->assertSame("https://prev.example/old/page", $child->SERVER["HTTP_REFERER"]);
        }

        public function testReplacesAnExistingReferer(): void {
            $child = $this->childOf([
                "REQUEST_SCHEME" => "https",
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/old/page",
                "HTTP_REFERER" => "https://older.example/",
            ]);
            $child->withPreviousAsReferer();

            $this->assertSame("https://prev.example/old/page", $child->SERVER["HTTP_REFERER"]);
        }

        // fromRequest()

        // POST values prefixed with the marker get rawurldecoded before they land in POST
        public function testRawurldecodesDecURIPrefixedPostValues(): void {
            $state = $this->fromRequestWith(post: ["raw" => self::DECODE_PREFIX . "foo%20bar%26baz"]);

            $this->assertSame(["raw" => "foo bar&baz"], $state->POST);
        }

        public function testLeavesPostValuesWithoutThePrefixUndecoded(): void {
            $state = $this->fromRequestWith(post: [
                "plain" => "a%20b",
                "inside" => "x" . self::DECODE_PREFIX . "a%20b",
            ]);

            $this->assertSame(["plain" => "a%20b", "inside" => "x" . self::DECODE_PREFIX . "a%20b"], $state->POST);
        }

        public function testDecodesPrefixedValuesInNestedArraysAndKeepsTheKeys(): void {
            $state = $this->fromRequestWith(post: [
                "list" => [self::DECODE_PREFIX . "a%20b", "c%20d"],
                "deep" => ["k%20" => ["inner" => self::DECODE_PREFIX . "e%26f"]],
            ]);

            $this->assertSame([
                "list" => ["a b", "c%20d"],
                "deep" => ["k%20" => ["inner" => "e&f"]],
            ], $state->POST);
        }

        public function testDecodesOnlyOnceAndKeepsAPlusSign(): void {
            $state = $this->fromRequestWith(post: [
                "twice" => self::DECODE_PREFIX . "%2520",
                "plus" => self::DECODE_PREFIX . "a+b",
            ]);

            $this->assertSame(["twice" => "%20", "plus" => "a+b"], $state->POST);
        }

        public function testAPrefixWithoutAValueDecodesToAnEmptyString(): void {
            $state = $this->fromRequestWith(post: ["empty" => self::DECODE_PREFIX]);

            $this->assertSame(["empty" => ""], $state->POST);
        }

        public function testTakesTheOtherRequestDataFromTheGlobals(): void {
            $state = $this->fromRequestWith(
                get: ["page" => "2"],
                cookie: ["session" => "abc"],
                files: ["upload" => ["name" => "a.txt"]],
                server: ["REQUEST_METHOD" => "POST", "HTTP_HOST" => "example.com"],
            );

            $this->assertSame(["page" => "2"], $state->GET);
            $this->assertSame(["session" => "abc"], $state->COOKIE);
            $this->assertSame(["upload" => ["name" => "a.txt"]], $state->FILES);
            $this->assertSame(["REQUEST_METHOD" => "POST", "HTTP_HOST" => "example.com"], $state->SERVER);
            $this->assertNull($state->previous);
            $this->assertNull($state->body, "No request body means null, not an empty string");
        }

        // The state is built from the superglobals, so they are set for the call and put back
        private function fromRequestWith(array $post = [], array $get = [], array $cookie = [], array $files = [], array $server = []): State {
            $saved = [$_SERVER, $_GET, $_POST, $_FILES, $_REQUEST, $_COOKIE];
            [$_SERVER, $_GET, $_POST, $_FILES, $_REQUEST, $_COOKIE] = [$server, $get, $post, $files, [], $cookie];

            try {
                return State::fromRequest();
            } finally {
                [$_SERVER, $_GET, $_POST, $_FILES, $_REQUEST, $_COOKIE] = $saved;
            }
        }

        // A state that was forked from one with the given SERVER values
        private function childOf(array $previousServer): State {
            $parent = $this->input();
            $parent->SERVER = $previousServer;

            return State::fromOverwrite($parent, []);
        }
    }

?>
