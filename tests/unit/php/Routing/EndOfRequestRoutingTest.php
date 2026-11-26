<?php

    namespace Tests\Routing;

    use FastRoute\DataGenerator\GroupCountBased;
    use FastRoute\RouteCollector;
    use FastRoute\RouteParser\Std;
    use ReflectionClass;
    use RuntimeException;
    use Tests\TestCase;
    use ZubZet\Framework\Message\EndOfRequest;
    use ZubZet\Framework\Routing\PendingAction;
    use ZubZet\Framework\Routing\Route;
    use ZubZet\Framework\ZubZet;

    /**
     * Where the end of a request passes through the routing: middlewares and
     * after middlewares, reroutes and the one place that catches it. The
     * framework of these tests runs closures in place of controller actions
     * and records the order they ran in.
     */
    class EndOfRequestRoutingTest extends TestCase {

        private ScriptedFramework $framework;

        protected function setUp(): void {
            // Declares the global functions, the instance is replaced right away
            $this->bootFramework();

            $this->framework = (new ReflectionClass(ScriptedFramework::class))->newInstanceWithoutConstructor();
            $this->framework->setAttributes(["rootDirectory" => "", "rootFolder" => "/"]);
            ZubZet::$instance = $this->framework;

            $this->request($this->input()->withUrl("/reports/list"));
            $this->response();
        }

        protected function tearDown(): void {
            Route::$storedPrefixedGroups = [];
            parent::tearDown();
        }

        // execute()

        public function testExecuteReturnsWhenTheDispatchEndedTheRequest(): void {
            $this->framework->onDispatch = function() {
                echo "answer";
                throw new EndOfRequest;
            };

            ob_start();
            $this->framework->execute();

            $this->assertSame("answer", ob_get_clean());
        }

        public function testExecuteReturnsWhatADispatchThatDidNotEndReturns(): void {
            $this->framework->onDispatch = fn() => "returned";

            $this->assertSame("returned", $this->framework->execute());
        }

        public function testExecutePassesEveryOtherThrowableOn(): void {
            $this->framework->onDispatch = function() {
                throw new RuntimeException("broken");
            };

            $this->assertThrows(RuntimeException::class, "broken", fn() => $this->framework->execute());
        }

        // Reroutes

        // The action that rerouted must not continue once the rerouted one has answered
        public function testARerouteLetsTheEndOfTheReroutedActionPass(): void {
            $this->framework->onDispatch = function() {
                throw new EndOfRequest;
            };

            $this->endedRequest(fn() => $this->framework->reroute(["auth", "login"]));
        }

        public function testARerouteReturnsToItsCallerWhenTheReroutedActionReturned(): void {
            $this->framework->onDispatch = fn() => null;

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame(["auth", "login"], request()->getUrlParts());
        }

        public function testAFinalRerouteOfTheResponseEndsTheRequestAfterTheDispatch(): void {
            $dispatched = false;
            $this->framework->onDispatch = function() use (&$dispatched) {
                $dispatched = true;
            };

            $this->endedRequest(fn() => response()->reroute(["auth", "login"], false, true));

            $this->assertTrue($dispatched);
        }

        // executePath()

        public function testAPathIsMappedToItsControllerAndAction(): void {
            $this->framework->actions["ReportsController::action_list"] = fn() => null;

            $this->framework->executePath(["reports", "list"]);

            $this->assertSame(["ReportsController::action_list"], $this->framework->ran);
        }

        public function testOneRerouteMoreThanAllowedEndsTheRequestWithoutRunningAnAction(): void {
            $this->framework->reroutes = $this->framework->maxReroutes;

            $printed = $this->endedRequest(fn() => $this->framework->executePath(["reports", "list"]));

            $this->assertSame("Error: Too many reroutes. Please contact the webmaster.", $printed);
            $this->assertSame([], $this->framework->ran);
        }

        public function testTheLastAllowedRerouteStillRuns(): void {
            $this->framework->actions["ReportsController::action_list"] = fn() => null;
            $this->framework->reroutes = $this->framework->maxReroutes - 1;

            $this->framework->executePath(["reports", "list"]);

            $this->assertSame(["ReportsController::action_list"], $this->framework->ran);
        }

        // Group middlewares of an action that is found by its name

        public function testTheAfterMiddlewareOfAGroupRunsAfterAnActionThatReturned(): void {
            $this->group("/reports");

            Route::performStoredGroupsMatchingPrefix(["reports", "list"], $this->action("action"));

            $this->assertSame(["Guard::before", "action", "Guard::after"], $this->framework->ran);
        }

        public function testTheAfterMiddlewareOfAGroupRunsAfterAnActionThatEndedTheRequest(): void {
            $this->group("/reports");

            $this->endedRequest(fn() => Route::performStoredGroupsMatchingPrefix(["reports", "list"], $this->endingAction("action")));

            $this->assertSame(["Guard::before", "action", "Guard::after"], $this->framework->ran);
        }

        /** @dataProvider blockingResults */
        public function testAGroupMiddlewareThatDoesNotReturnTrueEndsTheRequestBeforeTheAction(mixed $result): void {
            $this->group("/reports", before: fn() => $result);

            $this->endedRequest(fn() => Route::performStoredGroupsMatchingPrefix(["reports", "list"], $this->action("action")));

            $this->assertSame(["Guard::before"], $this->framework->ran);
        }

        public static function blockingResults(): array {
            return [
                "false" => [false],
                "nothing" => [null],
                "a value that only looks true" => [1],
            ];
        }

        public function testAGroupMiddlewareThatEndsTheRequestItselfSkipsActionAndAfterMiddleware(): void {
            $this->group("/reports", before: function() {
                throw new EndOfRequest;
            });

            $this->endedRequest(fn() => Route::performStoredGroupsMatchingPrefix(["reports", "list"], $this->action("action")));

            $this->assertSame(["Guard::before"], $this->framework->ran);
        }

        public function testAnActionThatFailsDoesNotGetTheAfterMiddleware(): void {
            $this->group("/reports");
            $failing = function() {
                $this->framework->ran[] = "action";
                throw new RuntimeException("broken");
            };

            $this->assertThrows(RuntimeException::class, "broken", fn() => Route::performStoredGroupsMatchingPrefix(["reports", "list"], $failing));

            $this->assertSame(["Guard::before", "action"], $this->framework->ran);
        }

        public function testAGroupOfAnotherPrefixDoesNotRun(): void {
            $this->group("/invoices");

            Route::performStoredGroupsMatchingPrefix(["reports", "list"], $this->action("action"));

            $this->assertSame(["action"], $this->framework->ran);
        }

        // Middlewares of an explicit route

        public function testTheAfterMiddlewareOfARouteRunsAfterAnActionThatReturned(): void {
            $handler = $this->route($this->action("action"));

            $handler([]);

            $this->assertSame(["Guard::before", "action", "Guard::after"], $this->framework->ran);
        }

        public function testTheAfterMiddlewareOfARouteRunsAfterAnActionThatEndedTheRequest(): void {
            $handler = $this->route($this->endingAction("action"));

            $this->endedRequest(fn() => $handler([]));

            $this->assertSame(["Guard::before", "action", "Guard::after"], $this->framework->ran);
        }

        public function testTheAfterMiddlewareOfARouteRunsAfterAControllerActionThatEndedTheRequest(): void {
            $this->framework->actions["ReportsController::action_list"] = function() {
                throw new EndOfRequest;
            };
            $handler = $this->route(["ReportsController", "action_list"]);

            $this->endedRequest(fn() => $handler([]));

            $this->assertSame(["Guard::before", "ReportsController::action_list", "Guard::after"], $this->framework->ran);
        }

        public function testARouteMiddlewareThatDoesNotReturnTrueSkipsActionAndAfterMiddleware(): void {
            $handler = $this->route($this->action("action"), before: fn() => false);

            $handler([]);

            $this->assertSame(["Guard::before"], $this->framework->ran);
        }

        public function testARouteMiddlewareThatEndsTheRequestItselfSkipsActionAndAfterMiddleware(): void {
            $handler = $this->route($this->action("action"), before: function() {
                throw new EndOfRequest;
            });

            $this->endedRequest(fn() => $handler([]));

            $this->assertSame(["Guard::before"], $this->framework->ran);
        }

        // Two ends are still one end of the request
        public function testAnAfterMiddlewareMayEndTheRequestOfAnActionThatEndedIt(): void {
            $handler = $this->route($this->endingAction("action"), after: function() {
                echo "after";
                throw new EndOfRequest;
            });

            $printed = $this->endedRequest(fn() => $handler([]));

            $this->assertSame("after", $printed);
            $this->assertSame(["Guard::before", "action", "Guard::after"], $this->framework->ran);
        }

        // An action that records its run and returns
        private function action(string $name): \Closure {
            return function() use ($name) {
                $this->framework->ran[] = $name;
            };
        }

        // An action that records its run and ends the request
        private function endingAction(string $name): \Closure {
            return function() use ($name) {
                $this->framework->ran[] = $name;
                throw new EndOfRequest;
            };
        }

        // Stores a group with one middleware and one after middleware
        private function group(string $prefix, ?callable $before = null, ?callable $after = null): void {
            $this->guard($before, $after);

            Route::$storedPrefixedGroups[$prefix] = [
                "middleware" => [new PendingAction(["Guard", "before"])],
                "afterMiddleware" => [new PendingAction(["Guard", "after"])],
            ];
        }

        // Registers a route with one middleware and one after middleware and returns its handler
        private function route(array|callable $action, ?callable $before = null, ?callable $after = null): callable {
            $this->guard($before, $after);

            $collector = new RouteCollector(new Std, new GroupCountBased);
            Route::init($this->framework, $collector);
            Route::performRoute(
                "GET",
                "/reports/list",
                new PendingAction($action),
                [new PendingAction(["Guard", "before"])],
                [new PendingAction(["Guard", "after"])],
            );

            return $collector->getData()[0]["GET"]["/reports/list"];
        }

        // The middleware passes and the after middleware returns unless told otherwise
        private function guard(?callable $before, ?callable $after): void {
            $this->framework->actions["Guard::before"] = $before ?? fn() => true;
            $this->framework->actions["Guard::after"] = $after ?? fn() => null;
        }
    }

    // Runs closures in place of the dispatch and of controller actions
    class ScriptedFramework extends ZubZet {

        /** @var ?callable What a dispatch does */
        public $onDispatch = null;

        /** @var array<string, callable> By "Controller::action" */
        public array $actions = [];

        /** @var string[] The actions in the order they ran */
        public array $ran = [];

        protected function dispatch(?array $customUrlParts = null) {
            return ($this->onDispatch)();
        }

        public function executeControllerAction($controller, $action, array $params = [], array $arguments = []) {
            $name = "$controller::$action";
            $this->ran[] = $name;

            return ($this->actions[$name])();
        }
    }

?>
