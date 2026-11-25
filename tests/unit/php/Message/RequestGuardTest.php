<?php

    namespace Tests\Message;

    use ReflectionClass;
    use Tests\TestCase;
    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\ZubZet;

    /**
     * The guards of a request: checkPermission(), requireOrganization(),
     * requireFreshTwoFactor() and csrfBlockAdditionalMethod(). A failed guard
     * shows a page and ends the request, which a test could not survive while
     * that was an exit. The framework of these tests records the page a guard
     * shows instead of running its controller.
     */
    class RequestGuardTest extends TestCase {

        private PageRecordingFramework $framework;

        protected function setUp(): void {
            // Declares the global functions, the instance is replaced right away
            $this->bootFramework();

            $this->framework = (new ReflectionClass(PageRecordingFramework::class))->newInstanceWithoutConstructor();
            $this->framework->setAttributes([
                "host" => "https://app.example",
                "rootDirectory" => "",
                "csrf_allow" => "same-origin",
                "csrf_trusted_origins" => "",
                "csrf_blocked_origins" => "",
            ]);
            ZubZet::$instance = $this->framework;
        }

        // checkPermission()

        public function testAGuestIsShownTheLoginAndTheRequestEnds(): void {
            $this->currentUser();

            $this->endedRequest(fn() => $this->request()->checkPermission("reports.view"));

            $this->assertSame([["login", "index"]], $this->framework->shown);
        }

        public function testAUserWithoutThePermissionIsShownThe403PageAndTheRequestEnds(): void {
            $this->loggedInUser(["reports.view"]);

            $this->endedRequest(fn() => $this->request()->checkPermission("reports.edit"));

            $this->assertSame([["error", "403"]], $this->framework->shown);
        }

        public function testAUserWithThePermissionPassesAndNothingIsShown(): void {
            $this->loggedInUser(["reports.view"]);

            $this->assertTrue($this->request()->checkPermission("reports.view"));
            $this->assertSame([], $this->framework->shown);
        }

        /** @dataProvider wildcards */
        public function testAWildcardCoversThePermission(string $held): void {
            $this->loggedInUser([$held]);

            $this->assertTrue($this->request()->checkPermission("reports.edit"));
        }

        public static function wildcards(): array {
            return [
                "everything" => ["*.*"],
                "the group of the permission" => ["reports.*"],
            ];
        }

        public function testNothingBehindAFailedGuardRuns(): void {
            $this->loggedInUser();
            $request = $this->request();
            $reached = false;

            $this->endedRequest(function() use ($request, &$reached) {
                $request->checkPermission("reports.view");
                $reached = true;
            });

            $this->assertFalse($reached);
        }

        public function testAskedForABooleanAFailedCheckReturnsFalseAndShowsNothing(): void {
            $this->currentUser();
            $this->assertFalse($this->request()->checkPermission("reports.view", true));

            $this->loggedInUser();
            $this->assertFalse($this->request()->checkPermission("reports.view", true));

            $this->assertSame([], $this->framework->shown);
        }

        // Outside the console the permission "console" is held by nobody
        public function testTheConsolePermissionEndsARequestThatIsNotFromTheConsole(): void {
            $this->loggedInUser(["*.*"]);

            $this->endedRequest(fn() => $this->httpRequest()->checkPermission("console"));

            $this->assertSame([["error", "403"]], $this->framework->shown);
        }

        // checkSuperPermission()

        public function testThePermissionOfTheExecutingUserCountsForTheSuperCheckOnly(): void {
            $user = $this->loggedInUser();
            $user->execUserId = 2;
            $this->grant($user, 2, ["users.impersonate"]);

            $this->assertTrue($this->request()->checkSuperPermission("users.impersonate"));
            $this->assertFalse($this->request()->checkPermission("users.impersonate", true));
        }

        public function testAFailedSuperCheckEndsTheRequestWithThe403Page(): void {
            $this->loggedInUser();

            $this->endedRequest(fn() => $this->request()->checkSuperPermission("users.impersonate"));

            $this->assertSame([["error", "403"]], $this->framework->shown);
        }

        // requireOrganization()

        public function testAGuestWithoutOrganizationIsShownTheLogin(): void {
            $this->currentUser();

            $this->endedRequest(fn() => $this->request()->requireOrganization());

            $this->assertSame([["login", "index"]], $this->framework->shown);
        }

        public function testAUserWithoutOrganizationIsShownThe403Page(): void {
            $this->loggedInUser();

            $this->endedRequest(fn() => $this->request()->requireOrganization());

            $this->assertSame([["error", "403"]], $this->framework->shown);
        }

        public function testAskedForABooleanAMissingOrganizationReturnsFalse(): void {
            $this->loggedInUser();

            $this->assertFalse($this->request()->requireOrganization(true));
            $this->assertSame([], $this->framework->shown);
        }

        // requireFreshTwoFactor()

        public function testAGuestIsShownTheLoginInsteadOfTheTwoFactorCheck(): void {
            $this->currentUser();

            $this->endedRequest(fn() => $this->request()->requireFreshTwoFactor());

            $this->assertSame([["login", "index"]], $this->framework->shown);
        }

        public function testAskedForABooleanAGuestIsNotFresh(): void {
            $this->currentUser();

            $this->assertFalse($this->request()->requireFreshTwoFactor(boolResult: true));
            $this->assertSame([], $this->framework->shown);
        }

        // csrfBlockAdditionalMethod()

        public function testAGetFromAnotherSiteIsShownThe403PageOnceGetIsChecked(): void {
            $request = $this->requestFrom("cross-site", "https://evil.test");

            $this->endedRequest(fn() => $request->csrfBlockAdditionalMethod());

            $this->assertSame([["error", "403"]], $this->framework->shown);
        }

        public function testAGetFromTheSameOriginPassesTheAdditionalCheck(): void {
            $request = $this->requestFrom("same-origin", "https://app.example");

            $request->csrfBlockAdditionalMethod();

            $this->assertSame([], $this->framework->shown);
        }

        public function testAGetFromAnotherSitePassesWhenOnlyAnotherMethodIsAdded(): void {
            $request = $this->requestFrom("cross-site", "https://evil.test");

            $request->csrfBlockAdditionalMethod("HEAD");

            $this->assertSame([], $this->framework->shown);
        }

        // A GET request that names where it comes from
        private function requestFrom(string $site, string $origin): Request {
            $input = $this->input()->withMethod("GET");
            $input->SERVER["HTTP_SEC_FETCH_SITE"] = $site;
            $input->SERVER["HTTP_ORIGIN"] = $origin;

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

        // Adds the permissions of another user id, e.g. the executing user
        private function grant(User $user, int $userId, array $permissions): void {
            $cache = new \ReflectionProperty(User::class, "permissionsByUserCache");
            // Needed before PHP 8.1, deprecated since 8.5
            if(PHP_VERSION_ID < 80100) $cache->setAccessible(true);
            $cache->setValue($user, $cache->getValue($user) + [$userId => $permissions]);
        }
    }

    // Records the page a guard shows, instead of running the controller of that page
    class PageRecordingFramework extends ZubZet {

        /** @var array<int, string[]> */
        public array $shown = [];

        public function executePath($parts) {
            $this->shown[] = $parts;
        }
    }

?>
