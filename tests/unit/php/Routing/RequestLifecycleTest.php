<?php

    namespace Tests\Routing;

    use RuntimeException;
    use Tests\ApplicationTestCase;

    /**
     * Whole requests through the real router: how an action is found, what a
     * guard does to it, where it ends and what runs around it. The application
     * lives in fixtures/application.
     *
     * These cases used to need a browser test, because an action that answered
     * ended the process. What a client sees of an answer (headers, cookies,
     * status code) is still asserted by the e2e suite.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    class RequestLifecycleTest extends ApplicationTestCase {

        protected function setUp(): void {
            $this->bootApplication(__DIR__ . "/fixtures/application");
        }

        // Finding the action by its name

        public function testAPathIsAnsweredByTheActionOfItsName(): void {
            $this->assertSame("action", $this->visit("/probe/action"));
        }

        public function testAControllerWithoutAnActionInThePathRunsItsIndex(): void {
            $this->assertSame("index", $this->visit("/probe"));
        }

        public function testAnUnknownActionRunsTheFallbackOfTheController(): void {
            $this->assertSame("fallback", $this->visit("/probe/does-not-exist"));
        }

        /** @dataProvider spellings */
        public function testControllerAndActionAreFoundInAnySpelling(string $url): void {
            $this->assertSame("action", $this->visit($url));
        }

        public static function spellings(): array {
            return [
                "a capital first letter" => ["/Probe/Action"],
                "an action in upper case" => ["/probe/ACTION"],
                "a trailing slash" => ["/probe/action/"],
                "a query string" => ["/probe/action?page=2"],
            ];
        }

        public function testThePartsBehindTheActionAreItsParameters(): void {
            $this->assertSame("one,two,three", $this->visit("/probe/parameters/one/two/three"));
        }

        public function testAParameterIsComparedWithAnExpectedValue(): void {
            $this->assertSame("yes", $this->visit("/probe/first/expected"));
            $this->assertSame("no", $this->visit("/probe/first/another"));
        }

        public function testAnUnknownControllerIsAnsweredWithThe404Page(): void {
            $this->assertStringContainsString("Page not found", $this->visit("/nowhere/at-all"));
        }

        // Explicit routes

        public function testAnExplicitRouteRunsItsAction(): void {
            $this->assertSame("action", $this->visit("/explicit"));
        }

        public function testARouteOnlyAnswersItsMethod(): void {
            $this->assertStringContainsString("Page not found", $this->visit("/explicit-post", "GET"));
            $this->assertSame("success", $this->answerTo("/explicit-post", "POST")["result"]);
        }

        /** @dataProvider methods */
        public function testARouteForAnyMethodAnswersEveryMethod(string $method): void {
            $this->assertSame("action", $this->visit("/explicit-any", $method));
        }

        public static function methods(): array {
            return [["GET"], ["POST"], ["PUT"], ["PATCH"], ["DELETE"], ["OPTIONS"], ["HEAD"]];
        }

        public function testARouteHandsItsPlaceholdersAndArgumentsToTheAction(): void {
            $this->assertSame('route:{"userId":"42","postId":"7"}:a,b', $this->visit("/users/42/posts/7"));
        }

        public function testAPlaceholderArrivesInTheSpellingOfTheRequest(): void {
            $this->assertSame('route:{"userId":"Ada","postId":"X"}:a,b', $this->visit("/USERS/Ada/POSTS/X"));
        }

        public function testARouteMayBeAnsweredByAClosure(): void {
            $printed = $this->visit("/callback");

            $this->assertStringContainsString('"from": "callback"', $printed);
            $this->assertStringEndsWith("|afterware", $printed);
        }

        // Ending the request

        public function testNothingOfTheActionRunsAfterARestReply(): void {
            $printed = $this->visit("/probe/rest", "POST", ["name" => "Ada"]);

            $this->assertStringNotContainsString("after-success", $printed);
            $this->assertSame("Ada", json_decode($printed, true)["name"]);
        }

        public function testNothingOfTheActionRunsAfterAView(): void {
            $printed = $this->visit("/probe/render");

            $this->assertStringContainsString("<main>", $printed);
            $this->assertStringContainsString("<p>Hello Ada</p>", $printed);
            $this->assertStringNotContainsString("after-render", $printed);
        }

        public function testNothingOfTheActionRunsAfterARedirect(): void {
            $this->assertSame("", $this->visit("/probe/redirect"));
        }

        /** @dataProvider stops */
        public function testStopEndsTheRequestAndKeepsWhatWasPrinted(string $spelling): void {
            $this->assertSame("printed", $this->visit("/probe/stop/$spelling"));
        }

        public static function stops(): array {
            return [
                "of the request" => ["request"],
                "of the response" => ["response"],
                "the global helper" => ["helper"],
            ];
        }

        public function testAnActionThatDoesNotStopRunsToItsEnd(): void {
            $this->assertSame("printed|after-stop", $this->visit("/probe/stop/none"));
        }

        public function testACatchOfExceptionDoesNotKeepTheActionRunning(): void {
            $printed = $this->visit("/probe/catch");

            $this->assertStringNotContainsString("caught", $printed);
            $this->assertStringNotContainsString("after-catch", $printed);
        }

        public function testAFinallyBlockOfTheActionRuns(): void {
            $this->assertStringEndsWith("finally-ran", $this->visit("/probe/finally"));
        }

        public function testAnExceptionOfAnActionReachesTheCallerWhenErrorsAreShown(): void {
            $this->assertThrows(RuntimeException::class, "broken action", fn() => $this->visit("/probe/throws"));
        }

        public function testAnExceptionOfAnActionIsAnsweredWithThe500PageWhenErrorsAreHidden(): void {
            $this->application->showErrors = 0;

            $this->assertStringContainsString("This page seems to be broken", $this->visit("/probe/throws"));
        }

        // Guards

        public function testAGuestIsShownTheLoginInPlaceOfAGuardedAction(): void {
            $this->assertSame("login-page", $this->visit("/probe/guard"));
        }

        public function testAUserWithoutThePermissionIsShownThe403PageInPlaceOfTheAction(): void {
            $this->loggedInUser(["probe.other"]);

            $printed = $this->visit("/probe/guard");

            $this->assertStringContainsString("the permissions to visit this page", $printed);
            $this->assertStringNotContainsString("behind-guard", $printed);
        }

        public function testAUserWithThePermissionReachesTheAction(): void {
            $this->loggedInUser(["probe.view"]);

            $this->assertSame("behind-guard", $this->visit("/probe/guard"));
        }

        // Asked for a boolean the check answers, asked plainly it ends the request
        public function testBothShapesOfThePermissionCheck(): void {
            $this->assertSame("denied|login-page", $this->visit("/probe/ask"));

            $this->loggedInUser(["probe.view"]);
            $this->assertSame("allowed|passed", $this->visit("/probe/ask"));
        }

        public function testAnActionAnswersByThePermissionsOfItsUser(): void {
            $this->loggedInUser(["probe.own"]);
            $this->assertSame("own", $this->answerTo("/probe/views")["view"]);

            $this->loggedInUser(["probe.*"]);
            $this->assertSame("all", $this->answerTo("/probe/views")["view"]);
        }

        public function testTheConsolePermissionKeepsAnActionFromTheWeb(): void {
            $this->loggedInUser(["*.*"]);

            $printed = $this->visit("/probe/console");

            $this->assertStringContainsString("the permissions to visit this page", $printed);
            $this->assertStringNotContainsString("behind-console", $printed);
        }

        // Reroutes

        public function testARerouteRunsTheOtherActionAndReturnsToTheCaller(): void {
            $this->assertSame("action|after-reroute", $this->visit("/probe/reroute"));
        }

        public function testAFinalRerouteDoesNotReturnToTheCaller(): void {
            $this->assertSame("action", $this->visit("/probe/reroutefinal"));
        }

        public function testARerouteToAnActionThatAnswersEndsTheCallerToo(): void {
            $printed = $this->visit("/probe/rerouteanswered");

            $this->assertSame("success", json_decode($printed, true)["result"]);
            $this->assertStringNotContainsString("after-reroute", $printed);
        }

        public function testAnAliasKeepsThePartsItDoesNotReplace(): void {
            $this->assertSame("one,two", $this->visit("/probe/alias/one/two"));
        }

        public function testTheStoreOfTheRequestReachesTheReroutedAction(): void {
            $this->assertSame("store", $this->visit("/probe/store"));
        }

        public function testAnActionThatReroutesToItselfIsStopped(): void {
            $this->assertSame("Error: Too many reroutes. Please contact the webmaster.", $this->visit("/probe/loop"));
        }

        // After middlewares of a route

        /** @dataProvider endings */
        public function testTheAfterMiddlewareRunsHoweverTheActionEnded(string $url, string $marker): void {
            $this->loggedInUser();

            $printed = $this->visit($url);

            $this->assertStringContainsString($marker, $printed);
            $this->assertStringEndsWith("|afterware", $printed);
        }

        public static function endings(): array {
            return [
                "returned" => ["/after/returned", "action"],
                "a REST reply" => ["/after/rest", '"result": "success"'],
                "a view" => ["/after/render", "Hello Ada"],
                "a failed permission check" => ["/after/guard", "the permissions to visit this page"],
                "a redirect" => ["/after/redirect", ""],
                "a stop" => ["/after/stop", "printed"],
            ];
        }

        public function testTheAfterMiddlewareDoesNotRunAfterAnActionThatFailed(): void {
            $this->assertThrows(RuntimeException::class, "broken action", fn() => $this->visit("/after/throws"));

            $this->assertStringNotContainsString("afterware", $this->printed);
        }

        // Middlewares of a route

        public function testAMiddlewareThatReturnsTrueLetsTheActionRun(): void {
            $this->assertSame("passing|action|afterware", $this->visit("/before/passing"));
        }

        public function testAMiddlewareThatReturnsNothingKeepsActionAndAfterMiddlewareFromRunning(): void {
            $this->assertSame("blocked", $this->visit("/before/blocking"));
        }

        public function testAMiddlewareMayAnswerItself(): void {
            $printed = $this->visit("/before/refusing");

            $this->assertSame("refused", json_decode($printed, true)["message"]);
            $this->assertStringNotContainsString("afterware", $printed);
        }

        // Groups around actions that are found by their name

        public function testAGroupGuardsTheActionsBelowItsPrefix(): void {
            $this->assertSame("passing|returned|afterware", $this->visit("/guarded/returned"));
        }

        public function testTheAfterMiddlewareOfAGroupRunsAfterAnActionThatAnswered(): void {
            $printed = $this->visit("/guarded/rest");

            $this->assertStringStartsWith("passing|", $printed);
            $this->assertStringNotContainsString("after-success", $printed);
            $this->assertStringEndsWith("|afterware", $printed);
        }

        public function testABlockingMiddlewareOfAnInnerGroupKeepsTheActionFromRunning(): void {
            $this->assertSame("passing|blocked", $this->visit("/guarded/blocked"));
        }

        public function testAGroupDoesNotGuardAPathThatOnlyStartsWithItsPrefix(): void {
            $this->assertStringNotContainsString("passing|", $this->visit("/guardedness/returned"));
        }
    }

?>
