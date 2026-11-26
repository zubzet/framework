<?php

    namespace Tests;

    use ReflectionClass;
    use ZubZet\Framework\Message\Input\State;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\ZubZet;

    /**
     * The base of a test that sends a whole request through the framework
     * without a web server: the routes, controllers and views of a small
     * application on disk are dispatched by the real router.
     *
     *     $this->bootApplication(__DIR__ . "/fixtures/application");
     *     $this->loggedInUser(["reports.view"]);
     *     $printed = $this->visit("/reports/list");
     *
     * There is no database and no config file. The user is a guest until the
     * test logs one in. What only a client can see (headers, cookies, the
     * status code) stays with the e2e suite.
     *
     * Route files are loaded with require_once and controllers are classes, so
     * a process can boot an application only once: the tests of a subclass run
     * in a process of their own (@runTestsInSeparateProcesses).
     */
    abstract class ApplicationTestCase extends TestCase {

        protected InProcessFramework $application;

        /** What the last request printed, also when it threw */
        protected string $printed = "";

        /**
         * Boots the application in the given directory, which holds
         * Controllers/, Routes/ and Views/ like the app/ folder of a project.
         *
         * @param array<string, mixed> $settings Added to the settings an application needs
         */
        protected function bootApplication(string $directory, array $settings = []): void {
            // Declares the global functions, the instance is replaced right away
            $this->bootFramework();

            $this->application = (new ReflectionClass(InProcessFramework::class))->newInstanceWithoutConstructor();
            $this->application->setAttributes($settings + [
                "host" => "http://localhost",
                "root" => "http://localhost/",
                "rootDirectory" => "",
                "rootFolder" => "/",
                "pageName" => "Application",
                "z_framework_root" => dirname(__DIR__, 3) . "/src/",
                "z_controllers" => "$directory/Controllers/",
                "z_views" => "$directory/Views/",
                "z_models" => "$directory/Models/",
                "translations" => "$directory/Translations/",
                "routes" => "$directory/Routes/",
                "csrf_allow" => "same-origin",
                "csrf_trusted_origins" => "",
                "csrf_blocked_origins" => "",
            ]);

            // An exception of an action reaches the test instead of the 500 page
            $this->application->showErrors = 1;
            ZubZet::$instance = $this->application;

            $this->currentUser();
        }

        /**
         * Sends a request through the router and returns what was printed,
         * whether the action returned or ended the request.
         *
         * @param array<string, mixed> $post
         */
        protected function visit(string $url, string $method = "GET", array $post = []): string {
            $this->application->replaceRequest(
                $this->input()->withUrl($url)->withMethod($method)->withPost($post),
            );
            $this->response();

            // Every request starts with the full number of reroutes
            $this->application->reroutes = 0;

            ob_start();
            try {
                $this->application->execute();
            } finally {
                $this->printed = ob_get_clean();
            }

            return $this->printed;
        }

        /**
         * The decoded REST reply of a request.
         *
         * @param array<string, mixed> $post
         */
        protected function answerTo(string $url, string $method = "GET", array $post = []): array {
            return json_decode($this->visit($url, $method, $post), true, 512, JSON_THROW_ON_ERROR);
        }
    }

    // A framework whose requests do not come from the console, although the tests run there
    class InProcessFramework extends ZubZet {

        public function replaceRequest(State $newState) {
            // The store is there to pass data through internal redirects
            $store = $this->req?->store ?? [];

            $this->req = new class($newState) extends Request {
                public function isCli(): bool {
                    return false;
                }
            };
            $this->req->store = $store;
            $this->requestStack[] = $this->req;
        }
    }

?>
