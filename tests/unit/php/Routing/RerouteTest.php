<?php

    namespace Tests\Routing;

    use ReflectionClass;
    use Tests\TestCase;
    use ZubZet\Framework\Message\Input\State;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\Message\Response;
    use ZubZet\Framework\ZubZet;

    /**
     * A reroute forks the input of the current request, points it to the new
     * path and dispatches it. The dispatch needs routes and controllers, so
     * the framework of these tests only records the request it would dispatch.
     */
    class RerouteTest extends TestCase {

        private RecordingFramework $framework;

        protected function setUp(): void {
            $this->boot();
        }

        // The new request

        public function testTheReroutedRequestPointsToTheNewPath(): void {
            $this->current("/old/page");

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame("/auth/login", request()->input->SERVER["REQUEST_URI"]);
            $this->assertSame("/auth/login", request()->input->SERVER["REDIRECT_URL"]);
            $this->assertSame(["auth", "login"], request()->getUrlParts());
        }

        public function testTheReroutedRequestIsTheOneThatGetsDispatched(): void {
            $original = $this->current("/old/page");

            $this->framework->reroute(["auth", "login"]);

            $this->assertCount(1, $this->framework->dispatched);
            $this->assertSame(request(), $this->framework->dispatched[0]);
            $this->assertNotSame($original, request());
        }

        // A link is built below the root directory, so the rerouted path has to be too
        /** @dataProvider rootDirectories */
        public function testTheReroutedPathLiesBelowTheRootDirectory(string $rootDirectory, string $rootFolder, string $uri): void {
            $this->boot(["rootDirectory" => $rootDirectory, "rootFolder" => $rootFolder]);
            $this->current($rootFolder . "old/page");

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame($uri, request()->input->SERVER["REQUEST_URI"]);
            $this->assertSame(["auth", "login"], request()->getUrlParts());
        }

        public static function rootDirectories(): array {
            return [
                "no root directory" => ["", "/", "/auth/login"],
                "one folder" => ["sub", "/sub/", "/sub/auth/login"],
                "two folders" => ["sub/app", "/sub/app/", "/sub/app/auth/login"],
            ];
        }

        public function testAnEmptyPathReroutesToTheStartPage(): void {
            $this->current("/old/page");

            $this->framework->reroute([]);

            $this->assertSame("/", request()->input->SERVER["REQUEST_URI"]);
            $this->assertSame([], request()->getUrlParts());
        }

        // What the rerouted request keeps

        public function testTheReroutedRequestKeepsTheDataOfTheOriginalOne(): void {
            $input = $this->input()
                ->withUrl("https://app.example/old/page?page=2")
                ->withMethod("POST")
                ->withPost(["name" => "value"])
                ->withCookies(["z_login_token" => "token"])
                ->withFiles(["upload" => ["name" => "a.txt"]])
                ->withBody("raw body");
            $input->SERVER["HTTP_USER_AGENT"] = "Browser";
            $this->request($input);

            $this->framework->reroute(["auth", "login"]);

            $rerouted = request();
            $this->assertSame("POST", $rerouted->input->SERVER["REQUEST_METHOD"]);
            $this->assertSame("app.example", $rerouted->input->SERVER["HTTP_HOST"]);
            $this->assertSame("Browser", $rerouted->userAgent());
            $this->assertSame(["page" => "2"], $rerouted->getGet());
            $this->assertSame(["name" => "value"], $rerouted->getPost());
            $this->assertSame(["z_login_token" => "token"], $rerouted->getCookies());
            $this->assertSame(["upload" => ["name" => "a.txt"]], $rerouted->getFiles());
            $this->assertSame("raw body", $rerouted->getBody());
        }

        public function testTheReroutedRequestKeepsTheQueryString(): void {
            $this->request($this->input()->withUrl("/old/page?page=2&sort=name"));

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame("/auth/login?page=2&sort=name", request()->input->SERVER["REQUEST_URI"]);
            $this->assertSame("page=2&sort=name", request()->input->SERVER["QUERY_STRING"]);
            $this->assertSame(["auth", "login"], request()->getUrlParts());
        }

        // "Store values within the Request to pass through data within internal redirects"
        public function testTheStoreOfTheRequestSurvivesAReroute(): void {
            $this->current("/old/page")->store = ["reason" => "session expired", "attempt" => 2];

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame(["reason" => "session expired", "attempt" => 2], request()->store);
        }

        public function testTheStoreSurvivesARerouteOfAReroute(): void {
            $this->current("/old/page")->store["from"] = "first";

            $this->framework->reroute(["second", "page"]);
            request()->store["added"] = "second";
            $this->framework->reroute(["third", "page"]);

            $this->assertSame(["from" => "first", "added" => "second"], request()->store);
        }

        // The route parameters belong to the route that was matched, a reroute matches a new one
        public function testTheRouteParametersOfTheOriginalRequestAreNotCarriedOver(): void {
            $this->current("/users/5")->urlParameters = ["id" => "5"];

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame([], request()->getRouteParameter());
        }

        // The original request

        public function testTheOriginalRequestIsLeftAsItWas(): void {
            $original = $this->current("/old/page?page=2");
            $original->getUrlParts();

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame("/old/page?page=2", $original->input->SERVER["REQUEST_URI"]);
            $this->assertSame(["old", "page"], $original->getUrlParts());
            $this->assertArrayNotHasKey("argv", $original->input->SERVER);
        }

        public function testTheInputOfTheReroutedRequestPointsBackToTheOriginalOne(): void {
            $original = $this->current("/old/page");

            $this->framework->reroute(["second", "page"]);
            $second = request();
            $this->framework->reroute(["third", "page"]);

            $this->assertSame($second->input, request()->input->previous);
            $this->assertSame($original->input, request()->input->previous->previous);
            $this->assertNull($original->input->previous);
        }

        public function testEveryRerouteAddsItsRequestToTheStack(): void {
            $original = $this->current("/old/page");

            $this->framework->reroute(["second", "page"]);
            $second = request();
            $this->framework->reroute(["third", "page"]);

            $this->assertSame([$original, $second, request()], $this->framework->requestStack);
        }

        // The command line

        // argv[0] is the script and argv[1] the command that runs an action
        public function testTheReroutedPathReplacesTheArgumentsOfACommandLineCall(): void {
            $input = $this->input();
            $input->SERVER["argv"] = ["index.php", "run", "old/page"];
            $this->request($input);

            $this->framework->reroute(["auth", "login"]);

            $this->assertSame(["index.php", "run", "auth", "login"], request()->input->SERVER["argv"]);
        }

        // Response::reroute()

        public function testTheResponseReroutesToTheGivenPath(): void {
            $this->current("/old/page/7");

            $this->response()->reroute(["auth", "login"]);

            $this->assertSame(["auth", "login"], request()->getUrlParts());
        }

        // As an alias the path is laid over the current one, part by part
        /** @dataProvider aliases */
        public function testAnAliasOverlaysTheCurrentPath(string $uri, array $path, array $expected): void {
            $this->current($uri);

            $this->response()->reroute($path, true);

            $this->assertSame($expected, request()->getUrlParts());
        }

        public static function aliases(): array {
            return [
                "the controller is replaced, the rest stays" => ["/old/page/7", ["new"], ["new", "page", "7"]],
                "controller and action are replaced" => ["/old/page/7", ["new", "show"], ["new", "show", "7"]],
                "a single part of the middle" => ["/old/page/7", [1 => "show"], ["old", "show", "7"]],
                "an alias longer than the current path" => ["/old", ["new", "show", "7"], ["new", "show", "7"]],
                "an empty alias keeps the path" => ["/old/page/7", [], ["old", "page", "7"]],
            ];
        }

        public function testWithoutTheAliasFlagThePathReplacesTheCurrentOne(): void {
            $this->current("/old/page/7");

            $this->response()->reroute(["new"]);

            $this->assertSame(["new"], request()->getUrlParts());
        }

        /** @param array<string, mixed> $settings */
        private function boot(array $settings = []): void {
            // Declares the global functions, the instance is replaced right away
            $this->bootFramework();

            $this->framework = (new ReflectionClass(RecordingFramework::class))->newInstanceWithoutConstructor();
            $this->framework->setAttributes($settings + ["rootDirectory" => "", "rootFolder" => "/"]);
            ZubZet::$instance = $this->framework;
        }

        // Makes a request for the uri the current one
        private function current(string $uri): Request {
            return $this->request($this->input()->withUrl($uri));
        }
    }

    // Records the request a dispatch would start with, instead of running routes and controllers
    class RecordingFramework extends ZubZet {

        /** @var Request[] */
        public array $dispatched = [];

        public function execute(?array $customUrlParts = null) {
            $this->dispatched[] = request();
        }
    }

?>
