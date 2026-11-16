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

        // PHP documents HTTPS as "a non-empty value" for https, so "off" would read as on
        public function testHttpSchemeLeavesServerHttpsUnset(): void {
            $state = $this->input()->withUrl("http://example.com/");

            $this->assertSame("http", $state->SERVER["REQUEST_SCHEME"]);
            $this->assertArrayNotHasKey("HTTPS", $state->SERVER);
        }

        public function testHttpSchemeRemovesAnHttpsThatWasSetBefore(): void {
            $state = $this->input()->withUrl("https://example.com/");

            $state->withUrl("http://example.com/");

            $this->assertArrayNotHasKey("HTTPS", $state->SERVER);
            $this->assertFalse(!empty($state->SERVER["HTTPS"]), "The usual check for https");
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

        // RFC 3986: the query belongs to the path it came with, a new path has none
        public function testAUrlWithAPathAndNoQueryDropsThePriorQuery(): void {
            $state = $this->input()->withGet(["a" => "1"])->withPost(["p" => "kept"]);
            $state->withUrl("/other");

            $this->assertSame("/other", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/other", $state->SERVER["REDIRECT_URL"]);
            $this->assertSame("", $state->SERVER["QUERY_STRING"]);
            $this->assertSame([], $state->GET);
            $this->assertSame(["p" => "kept"], $state->REQUEST);
        }

        // withPath() is the way to change the path alone, a reroute relies on it
        public function testWithPathKeepsThePriorQueryThatWithUrlDrops(): void {
            $state = $this->input()->withUrl("/old?a=1");
            $state->withPath("/other");

            $this->assertSame("/other?a=1", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("a=1", $state->SERVER["QUERY_STRING"]);
            $this->assertSame(["a" => "1"], $state->GET);
        }

        /**
         * The reference resolution examples of RFC 3986, sections 5.4.1 and 5.4.2,
         * against the base "http://a/b/c/d;p?q". A fragment never reaches a
         * server and an empty path is the start page, otherwise the targets are
         * the ones of the RFC.
         *
         * @dataProvider rfc3986References
         */
        public function testWithUrlResolvesAReferenceLikeRfc3986(string $reference, string $target): void {
            $state = $this->input()->withUrl("http://a/b/c/d;p?q");

            $state->withUrl($reference);

            $this->assertSame(
                $target,
                $state->SERVER["REQUEST_SCHEME"] . "://" . $state->SERVER["HTTP_HOST"] . $state->SERVER["REQUEST_URI"],
            );
            $query = explode("?", $target, 2)[1] ?? "";
            $this->assertSame($query, $state->SERVER["QUERY_STRING"]);
            $this->assertSame(explode("?", $state->SERVER["REQUEST_URI"], 2)[0], $state->SERVER["REDIRECT_URL"]);
        }

        public static function rfc3986References(): array {
            $examples = [
                // 5.4.1, normal examples
                "g" => "http://a/b/c/g",
                "./g" => "http://a/b/c/g",
                "g/" => "http://a/b/c/g/",
                "/g" => "http://a/g",
                "//g" => "http://g/",
                "?y" => "http://a/b/c/d;p?y",
                "g?y" => "http://a/b/c/g?y",
                "#s" => "http://a/b/c/d;p?q",
                "g#s" => "http://a/b/c/g",
                "g?y#s" => "http://a/b/c/g?y",
                ";x" => "http://a/b/c/;x",
                "g;x" => "http://a/b/c/g;x",
                "g;x?y#s" => "http://a/b/c/g;x?y",
                "" => "http://a/b/c/d;p?q",
                "." => "http://a/b/c/",
                "./" => "http://a/b/c/",
                ".." => "http://a/b/",
                "../" => "http://a/b/",
                "../g" => "http://a/b/g",
                "../.." => "http://a/",
                "../../" => "http://a/",
                "../../g" => "http://a/g",
                // 5.4.2, abnormal examples
                "../../../g" => "http://a/g",
                "../../../../g" => "http://a/g",
                "/./g" => "http://a/g",
                "/../g" => "http://a/g",
                "g." => "http://a/b/c/g.",
                ".g" => "http://a/b/c/.g",
                "g.." => "http://a/b/c/g..",
                "..g" => "http://a/b/c/..g",
                "./../g" => "http://a/b/g",
                "./g/." => "http://a/b/c/g/",
                "g/./h" => "http://a/b/c/g/h",
                "g/../h" => "http://a/b/c/h",
                "g;x=1/./y" => "http://a/b/c/g;x=1/y",
                "g;x=1/../y" => "http://a/b/c/y",
                "g?y/./x" => "http://a/b/c/g?y/./x",
                "g?y/../x" => "http://a/b/c/g?y/../x",
                "g#s/./x" => "http://a/b/c/g",
                "g#s/../x" => "http://a/b/c/g",
                // A whole url
                "https://other.example/x?z" => "https://other.example/x?z",
                "https://other.example" => "https://other.example/",
            ];

            $references = [];
            foreach($examples as $reference => $target) {
                $references["'$reference'"] = [(string) $reference, $target];
            }
            return $references;
        }

        public function testARelativePathOnAStateWithoutAUriStartsAtTheRoot(): void {
            $state = $this->input()->withUrl("a/b");

            $this->assertSame("/a/b", $state->SERVER["REQUEST_URI"]);
        }

        public function testARelativePathIsResolvedNextToTheCurrentPathNotItsQuery(): void {
            $state = $this->input()->withUrl("/shop/list?next=/x/y");

            $state->withUrl("detail");

            $this->assertSame("/shop/detail", $state->SERVER["REQUEST_URI"]);
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

        // REQUEST is put together like PHP puts $_REQUEST together: from the sources its
        // request_order names, in that order, so a later one wins
        /** @dataProvider requestOrders */
        public function testRequestFollowsTheRequestOrder(string $order, array $expected): void {
            $state = $this->input($order)
                ->withGet(["common" => "get", "g" => "g"])
                ->withPost(["common" => "post", "p" => "p"])
                ->withCookies(["common" => "cookie", "c" => "c"]);

            $this->assertSame($expected, $state->REQUEST);
        }

        public static function requestOrders(): array {
            return [
                "php.ini-production, without the cookies" => ["GP", ["common" => "post", "g" => "g", "p" => "p"]],
                "the cookies last" => ["GPC", ["common" => "cookie", "g" => "g", "p" => "p", "c" => "c"]],
                "the cookies first" => ["CGP", ["common" => "post", "c" => "c", "g" => "g", "p" => "p"]],
                "the query after the post" => ["PG", ["common" => "get", "p" => "p", "g" => "g"]],
                "lower case letters" => ["gp", ["common" => "post", "g" => "g", "p" => "p"]],
                "the letters of variables_order that are no source" => ["EGPCS", ["common" => "cookie", "g" => "g", "p" => "p", "c" => "c"]],
                "only the query" => ["G", ["common" => "get", "g" => "g"]],
                "no source at all" => ["", []],
            ];
        }

        public function testAForkKeepsTheRequestOrderOfItsParent(): void {
            $parent = $this->input("GP")->withCookies(["c" => "cookie"]);

            $child = State::fromOverwrite($parent, ["GET" => ["g" => "get"]]);

            $this->assertSame(["g" => "get"], $child->REQUEST);
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

        // REQUEST follows PHP's own $_REQUEST

        // array_merge() renumbers integer keys, so ?1=a used to arrive as REQUEST[0]
        public function testRequestKeepsNumericKeysAndLetsThePostWin(): void {
            $state = $this->input()->withGet(["1" => "from get", "7" => "only get"])->withPost(["1" => "from post"]);

            $this->assertSame([1 => "from post", 7 => "only get"], $state->REQUEST);
        }

        public function testFromOverwriteRefreshesTheRequestOfTheChild(): void {
            $parent = $this->input()->withGet(["a" => "1"]);

            $child = State::fromOverwrite($parent, ["GET" => ["b" => "2"], "POST" => ["a" => "posted"]]);

            $this->assertSame(["a" => "posted", "b" => "2"], $child->REQUEST);
            $this->assertSame(["a" => "1"], $parent->REQUEST, "The parent is left intact");
        }

        public function testFromOverwriteAppliesAnExplicitRequestLast(): void {
            $child = State::fromOverwrite($this->input(), ["GET" => ["a" => "1"], "REQUEST" => ["a" => "explicit"]]);

            $this->assertSame(["a" => "explicit"], $child->REQUEST);
        }

        // body and previous are properties too, but no arrays that could be merged into
        /** @dataProvider propertiesThatAreNoArrays */
        public function testFromOverwriteRejectsAPropertyThatHoldsNoArray(string $type): void {
            $this->assertThrows(
                InvalidArgumentException::class,
                "OverwriteData includes unknown type: '$type'.",
                fn() => State::fromOverwrite($this->input(), [$type => ["k" => "v"]]),
            );
        }

        public static function propertiesThatAreNoArrays(): array {
            return [
                "body" => ["body"],
                "previous" => ["previous"],
            ];
        }

        // The POST is decoded, so the same value in REQUEST has to be decoded too
        public function testFromRequestDecodesThePostValuesInTheRequestToo(): void {
            $raw = ["raw" => self::DECODE_PREFIX . "a%20b", "nested" => ["deep" => self::DECODE_PREFIX . "c%26d"]];

            $state = $this->fromRequestWith(post: $raw, get: ["q" => "kept"], request: $raw + ["q" => "kept"]);

            $this->assertSame(["raw" => "a b", "nested" => ["deep" => "c&d"]], $state->POST);
            $this->assertSame(["raw" => "a b", "nested" => ["deep" => "c&d"], "q" => "kept"], $state->REQUEST);
        }

        // Only the POST is decoded, a GET value that looks the same stays as sent
        public function testFromRequestLeavesARequestValueThatIsNotFromThePostAlone(): void {
            $prefixed = self::DECODE_PREFIX . "a%20b";

            $state = $this->fromRequestWith(post: ["other" => "x"], get: ["q" => $prefixed], request: ["q" => $prefixed, "other" => "x"]);

            $this->assertSame(["q" => $prefixed, "other" => "x"], $state->REQUEST);
        }

        // withUrl() and withPath(), the details

        public function testWithUrlKeepsThePortInTheHost(): void {
            $state = $this->input()->withUrl("https://h.example:8443/x");

            $this->assertSame("h.example:8443", $state->SERVER["HTTP_HOST"]);
        }

        public function testWithUrlReadsTheSchemeRegardlessOfCase(): void {
            $state = $this->input()->withUrl("HTTPS://h.example/x");

            $this->assertSame("https", $state->SERVER["REQUEST_SCHEME"]);
            $this->assertSame("on", $state->SERVER["HTTPS"]);
        }

        // "0" is a query string, PHP's empty() takes it for none
        public function testWithPathKeepsAQueryStringOfZero(): void {
            $state = $this->input();
            $state->SERVER["QUERY_STRING"] = "0";

            $state->withPath("/p");

            $this->assertSame("/p?0", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/p", $state->SERVER["REDIRECT_URL"]);
        }

        // withGet(), the details

        // strtok() skips a leading "?", so the old query used to become the path: "old=1?a=1"
        public function testWithGetReplacesTheQueryOfAUriThatHasNoPath(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "?old=1";

            $state->withGet(["a" => "1"]);

            $this->assertSame("?a=1", $state->SERVER["REQUEST_URI"]);
        }

        // strtok() keeps one position for the whole process
        public function testWithGetLeavesATokenizationOfTheCallerAlone(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/p?x";

            $first = strtok("a b c", " ");
            $state->withGet(["q" => "1"]);
            $second = strtok(" ");

            $this->assertSame(["a", "b"], [$first, $second]);
        }

        // http_build_query() leaves a null value out, so there is no query to announce
        public function testWithGetWritesNoQuestionMarkWhenTheQueryStringIsEmpty(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/p?old=1";

            $state->withGet(["a" => null]);

            $this->assertSame("", $state->SERVER["QUERY_STRING"]);
            $this->assertSame("/p", $state->SERVER["REQUEST_URI"]);
        }

        public function testWithGetKeepsEverythingBeforeTheFirstQuestionMark(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/p?a=1?b=2";

            $state->withGet(["c" => "3"]);

            $this->assertSame("/p?c=3", $state->SERVER["REQUEST_URI"]);
        }

        // withUrl() against what a web server hands to PHP

        // QUERY_STRING and REQUEST_URI carry the query as it was sent, only GET is decoded
        /** @dataProvider rawQueries */
        public function testWithUrlKeepsTheQueryStringAsItWasGiven(string $query, array $get): void {
            $state = $this->input()->withUrl("/search?$query");

            $this->assertSame($query, $state->SERVER["QUERY_STRING"]);
            $this->assertSame("/search?$query", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/search", $state->SERVER["REDIRECT_URL"]);
            $this->assertSame($get, $state->GET);
            $this->assertSame($get, $state->REQUEST);
        }

        public static function rawQueries(): array {
            return [
                "a percent encoded space" => ["q=a%20b", ["q" => "a b"]],
                "a plus as a space" => ["q=a+b", ["q" => "a b"]],
                "list brackets" => ["tags[]=x&tags[]=y", ["tags" => ["x", "y"]]],
                "named brackets" => ["f[a][b]=1", ["f" => ["a" => ["b" => "1"]]]],
                "a key without a value" => ["flag", ["flag" => ""]],
                "an empty value" => ["e=", ["e" => ""]],
                "a repeated key, the last one wins" => ["a=1&a=2", ["a" => "2"]],
                "a dot in a key becomes an underscore, like in \$_GET" => ["a.b=1", ["a_b" => "1"]],
                "an encoded ampersand and equals sign" => ["q=a%26b%3Dc", ["q" => "a&b=c"]],
                "a numeric key" => ["5=five", [5 => "five"]],
                "a zero" => ["0", [0 => ""]],
            ];
        }

        // What PHP itself makes of a query string, so the state and $_GET cannot drift apart
        /** @dataProvider rawQueries */
        public function testWithUrlFillsGetLikePhpFillsItsOwn(string $query): void {
            parse_str($query, $expected);

            $this->assertSame($expected, $this->input()->withUrl("/p?$query")->GET);
        }

        public function testWithUrlLeavesTheFragmentOut(): void {
            $state = $this->input()->withUrl("/p?a=1#section");

            $this->assertSame("/p?a=1", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("a=1", $state->SERVER["QUERY_STRING"]);
        }

        public function testWithUrlClearsTheQueryForAUrlThatEndsInAQuestionMark(): void {
            $state = $this->input()->withGet(["a" => "1"]);

            $state->withUrl("/p?");

            $this->assertSame("/p", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("", $state->SERVER["QUERY_STRING"]);
            $this->assertSame([], $state->GET);
        }

        // parse_url() takes ":1" for a port and gives up, the request itself accepts such a path
        /** @dataProvider pathsThatParseUrlRejects */
        public function testWithUrlAcceptsAPathThatParseUrlRejects(string $url, string $uri, string $path, array $get): void {
            $state = $this->input()->withUrl($url);

            $this->assertSame($uri, $state->SERVER["REQUEST_URI"]);
            $this->assertSame($path, $state->SERVER["REDIRECT_URL"]);
            $this->assertSame($get, $state->GET);
        }

        public static function pathsThatParseUrlRejects(): array {
            return [
                "a segment ending in a colon and digits" => ["/x:1", "/x:1", "/x:1", []],
                "the same with a query" => ["/a/b:1?y=2", "/a/b:1?y=2", "/a/b:1", ["y" => "2"]],
                "the same with a fragment" => ["/a/b:1#top", "/a/b:1", "/a/b:1", []],
            ];
        }

        public function testWithUrlRejectsAUrlThatCannotBeRead(): void {
            $this->assertThrows(
                InvalidArgumentException::class,
                "Cannot read the url 'http:///x'.",
                fn() => $this->input()->withUrl("http:///x"),
            );
        }

        // A url with a host names a whole target, "https://example.com" is its start page
        public function testWithUrlOfAHostWithoutAPathPointsToTheStartPage(): void {
            $state = $this->input();
            $state->SERVER["REQUEST_URI"] = "/old/page";

            $state->withUrl("https://example.com");

            $this->assertSame("example.com", $state->SERVER["HTTP_HOST"]);
            $this->assertSame("/", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/", $state->SERVER["REDIRECT_URL"]);
        }

        public function testWithUrlKeepsTheBracketsAndThePortOfAnIpv6Host(): void {
            $state = $this->input()->withUrl("http://[::1]:8080/p");

            $this->assertSame("[::1]:8080", $state->SERVER["HTTP_HOST"]);
            $this->assertSame("/p", $state->SERVER["REQUEST_URI"]);
        }

        public function testWithUrlKeepsAnEncodedPathEncoded(): void {
            $state = $this->input()->withUrl("/a%20b/c%2Fd");

            $this->assertSame("/a%20b/c%2Fd", $state->SERVER["REQUEST_URI"]);
            $this->assertSame("/a%20b/c%2Fd", $state->SERVER["REDIRECT_URL"]);
        }

        public function testWithUrlLeavesTheLoginOfAUrlOutOfTheHost(): void {
            $state = $this->input()->withUrl("https://user:secret@h.example/p");

            $this->assertSame("h.example", $state->SERVER["HTTP_HOST"]);
            $this->assertSame("/p", $state->SERVER["REQUEST_URI"]);
        }

        // fromOverwrite(), the details

        /** @dataProvider overwritesThatAreNoArrays */
        public function testFromOverwriteRejectsValuesThatAreNoArray(mixed $values): void {
            $this->assertThrows(
                InvalidArgumentException::class,
                "OverwriteData for 'GET' must be an array.",
                fn() => State::fromOverwrite($this->input(), ["GET" => $values]),
            );
        }

        public static function overwritesThatAreNoArrays(): array {
            return [
                "a string" => ["a=1"],
                "null" => [null],
                "an object" => [new \stdClass],
            ];
        }

        // Every array of the child is its own copy, also the nested ones
        /** @dataProvider overwritableTypes */
        public function testAChangeToTheChildNeverReachesTheParent(string $type): void {
            $parent = $this->input();
            $parent->{$type} = ["kept" => "parent", "nested" => ["deep" => "parent"]];

            $child = State::fromOverwrite($parent, [$type => ["added" => "child"]]);
            $child->{$type}["kept"] = "child";
            $child->{$type}["nested"]["deep"] = "child";

            $this->assertSame(["kept" => "parent", "nested" => ["deep" => "parent"]], $parent->{$type});
        }

        public static function overwritableTypes(): array {
            return [
                "SERVER" => ["SERVER"],
                "GET" => ["GET"],
                "POST" => ["POST"],
                "FILES" => ["FILES"],
                "REQUEST" => ["REQUEST"],
                "SESSION" => ["SESSION"],
                "COOKIE" => ["COOKIE"],
            ];
        }

        public function testAForkKeepsTheBodyOfItsParent(): void {
            $parent = $this->input()->withBody('{"a":1}');

            $child = State::fromOverwrite($parent);
            $child->withBody("changed");

            $this->assertSame('{"a":1}', $parent->body);
            $this->assertSame("changed", $child->body);
        }

        // A copy of a fork must not share the pointer to the parent with the fork
        public function testACloneOfAForkHasItsOwnPreviousPointer(): void {
            $child = State::fromOverwrite($this->input());

            $copy = clone $child;
            $copy->previous = null;

            $this->assertNotNull($child->previous);
        }

        public function testAnOverwriteOfANestedValueReplacesItAsAWhole(): void {
            $parent = $this->input();
            $parent->POST = ["nested" => ["a" => "1", "b" => "2"]];

            $child = State::fromOverwrite($parent, ["POST" => ["nested" => ["b" => "changed"]]]);

            $this->assertSame(["nested" => ["b" => "changed"]], $child->POST);
        }

        // fromRequest(), the details

        // An application that fills $_POST itself, from a JSON body for example, has values that are no text
        public function testFromRequestLeavesPostValuesThatAreNoTextAsTheyAre(): void {
            $post = ["number" => 5, "nothing" => null, "flag" => true, "list" => [1.5, null]];

            // Reading null as text is deprecated since PHP 8.1
            $complaints = [];
            set_error_handler(function(int $level, string $message) use (&$complaints) {
                $complaints[] = $message;
                return true;
            });

            try {
                $state = $this->fromRequestWith(post: $post);
            } finally {
                restore_error_handler();
            }

            $this->assertSame([], $complaints);
            $this->assertSame($post, $state->POST);
        }

        public function testFromRequestComparesThePrefixExactly(): void {
            $state = $this->fromRequestWith(post: [
                "upper" => "<#DECURI#>a%20b",
                "short" => "<#decURI#",
                "spaced" => " " . self::DECODE_PREFIX . "a%20b",
            ]);

            $this->assertSame([
                "upper" => "<#DECURI#>a%20b",
                "short" => "<#decURI#",
                "spaced" => " " . self::DECODE_PREFIX . "a%20b",
            ], $state->POST);
        }

        // The state is a copy: what a test or an action does to it stays out of PHP's own arrays
        public function testAChangeToTheStateDoesNotReachTheSuperglobals(): void {
            $saved = [$_GET, $_COOKIE, $_SERVER];
            [$_GET, $_COOKIE] = [["a" => "1"], ["c" => "1"]];

            try {
                $state = State::fromRequest();
                $state->withGet(["a" => "changed"])->withCookies([])->withMethod("DELETE");

                $this->assertSame(["a" => "1"], $_GET);
                $this->assertSame(["c" => "1"], $_COOKIE);
                $this->assertSame($saved[2], $_SERVER);
            } finally {
                [$_GET, $_COOKIE, $_SERVER] = $saved;
            }
        }

        // The state is built from the superglobals, so they are set for the call and put back
        private function fromRequestWith(array $post = [], array $get = [], array $cookie = [], array $files = [], array $server = [], array $request = []): State {
            $saved = [$_SERVER, $_GET, $_POST, $_FILES, $_REQUEST, $_COOKIE];
            [$_SERVER, $_GET, $_POST, $_FILES, $_REQUEST, $_COOKIE] = [$server, $get, $post, $files, $request, $cookie];

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
