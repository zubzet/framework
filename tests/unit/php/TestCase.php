<?php

    namespace Tests;

    use PHPUnit\Framework\TestCase as BaseTestCase;
    use ReflectionClass;
    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Core\Constants;
    use ZubZet\Framework\Database\Endpoint;
    use ZubZet\Framework\Message\Input\State;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\Message\Response;
    use ZubZet\Framework\Support\Filesystem;
    use ZubZet\Framework\Support\GlobalReferences;
    use ZubZet\Framework\Support\Helpers;
    use ZubZet\Framework\Support\RuntimeCache\RuntimeCache;
    use ZubZet\Framework\ZubZet;

    /**
     * The base of every unit test that needs more than the class under test.
     *
     * A test asks for what it needs and gets it without a web server, a database
     * or a config file:
     *
     *     $this->bootFramework(["rootDirectory" => "sub"]);
     *     $request = $this->request($this->input()->withUrl("http://localhost/sub/a/b"));
     *
     * Everything handed out here is taken back after the test, so no test sees the
     * settings, the request or the cached values of another one.
     */
    abstract class TestCase extends BaseTestCase {

        /** @var string[] */
        private array $temporaryDirectories = [];

        protected function tearDown(): void {
            ZubZet::$instance = null;
            $this->clearRuntimeCaches();
            $this->clearEndpoint();

            foreach($this->temporaryDirectories as $directory) {
                Filesystem::remove($directory);
            }
            $this->temporaryDirectories = [];

            parent::tearDown();
        }

        /**
         * Provides the framework instance the global helpers (zubzet(), config(),
         * request(), ...) work on. It holds exactly the given settings: nothing is
         * read from a config file, nothing connects, and there is no request,
         * response, database or user until the test sets one.
         *
         * @param array<string, mixed> $settings What config() returns
         */
        protected function bootFramework(array $settings = []): ZubZet {
            // The constructor would read the config file and open the request
            $framework = (new ReflectionClass(ZubZet::class))->newInstanceWithoutConstructor();
            $framework->setAttributes($settings);
            ZubZet::$instance = $framework;

            // Loading these classes declares the global functions and constants
            new GlobalReferences;
            new Constants;
            new Helpers;

            return $framework;
        }

        /**
         * Changes single settings of the booted framework and keeps the others.
         *
         * @param array<string, mixed> $settings
         */
        protected function setSettings(array $settings): void {
            zubzet()->overwriteAttributes($settings);
        }

        /** Removes a setting, as if its line was missing in the config file. */
        protected function removeSetting(string $key): void {
            $settings = zubzet()->getAllAttributes();
            unset($settings[$key]);
            zubzet()->setAttributes($settings);
        }

        /**
         * An input without any data. The with...() methods of the state fill it:
         *
         *     $this->input()->withMethod("POST")->withPost(["name" => "value"])
         *
         * REQUEST is put together from GET, POST and COOKIE in the given order,
         * whatever the php.ini of the machine says, so a test sees the same
         * REQUEST everywhere.
         */
        protected function input(string $requestOrder = "GPC"): State {
            $input = new class($requestOrder) extends State {

                public function __construct(private string $fixedRequestOrder) {}

                protected function requestOrder(): string {
                    return $this->fixedRequestOrder;
                }
            };
            $input->body = null;
            $input->SERVER = [];
            $input->GET = [];
            $input->POST = [];
            $input->FILES = [];
            $input->REQUEST = [];
            $input->SESSION = [];
            $input->COOKIE = [];
            return $input;
        }

        /**
         * Makes the given input the current request, the one request() returns.
         * Boots the framework without settings when the test has not done so.
         */
        protected function request(?State $input = null): Request {
            $framework = ZubZet::$instance ?? $this->bootFramework();

            $framework->req = new Request($input ?? $this->input());
            $framework->requestStack[] = $framework->req;

            return $framework->req;
        }

        /**
         * Makes a new response the current one, the one response() returns.
         * Boots the framework without settings when the test has not done so.
         */
        protected function response(): Response {
            $framework = ZubZet::$instance ?? $this->bootFramework();

            $framework->res = new Response();
            $framework->responseStack[] = $framework->res;

            return $framework->res;
        }

        /**
         * Makes a user without database access the current one, the one user()
         * returns. Its constructor would identify it through the database, so
         * it is left out: the user is anonymous unless the test says otherwise.
         */
        protected function currentUser(bool $loggedIn = false): User {
            $framework = ZubZet::$instance ?? $this->bootFramework();

            $user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
            $user->isLoggedIn = $loggedIn;
            $framework->user = $user;

            return $user;
        }

        /**
         * Skips the test on a PHP built without Argon2. The framework hashes with
         * PASSWORD_ARGON2ID unconditionally and composer.json does not require
         * the support, so the tests that reach it have to tolerate its absence.
         */
        protected function requireArgon2id(): void {
            if(defined("PASSWORD_ARGON2ID")) return;

            $this->markTestSkipped("This PHP was built without Argon2 support.");
        }

        /** An empty directory with a trailing slash, removed after the test. */
        protected function temporaryDirectory(): string {
            $directory = sys_get_temp_dir() . "/zubzet-unit-" . uniqid() . "/";
            mkdir($directory);

            $this->temporaryDirectories[] = $directory;
            return $directory;
        }

        /**
         * Runs the action with the given directory as the working directory, for
         * code that reads relative paths. The previous directory is restored
         * afterwards, also when the action throws.
         */
        protected function inDirectory(string $directory, callable $action): mixed {
            $previous = getcwd();
            chdir($directory);

            try {
                return $action();
            } finally {
                chdir($previous);
            }
        }

        /**
         * Asserts that the action throws exactly this class, no subclass of it,
         * with exactly this message. expectException() accepts subclasses and
         * a message that only contains the expected one.
         *
         * @param class-string<\Throwable> $class
         */
        protected function assertThrows(string $class, string $message, callable $action): \Throwable {
            try {
                $action();
            } catch(\Throwable $thrown) {
                $this->assertSame($class, get_class($thrown), "Unexpected exception: " . $thrown->getMessage());
                $this->assertSame($message, $thrown->getMessage());
                return $thrown;
            }

            $this->fail("Expected a $class, but nothing was thrown.");
        }

        /**
         * Calls a method that is not public. Only for logic that has no public
         * way in; a test goes through the public method wherever there is one.
         *
         * @param object|class-string $target An object, or a class name for a static method
         */
        protected function callHidden(object|string $target, string $method, mixed ...$arguments): mixed {
            $reflection = new \ReflectionMethod($target, $method);
            // Needed before PHP 8.1, deprecated since 8.5
            if(PHP_VERSION_ID < 80100) $reflection->setAccessible(true);

            return $reflection->invoke(is_object($target) ? $target : null, ...$arguments);
        }

        /**
         * Sets a static property that is not public. Only for state a class
         * keeps for the whole process and offers no way to reset.
         *
         * @param class-string $class
         */
        protected function setHiddenStatic(string $class, string $property, mixed $value): void {
            (new ReflectionClass($class))->setStaticPropertyValue($property, $value);
        }

        // RuntimeCache and the classes extending it share one private store
        private function clearRuntimeCaches(): void {
            $this->setHiddenStatic(RuntimeCache::class, "cache", []);
        }

        // Endpoint::get() keeps the settings it was first asked for
        private function clearEndpoint(): void {
            $this->setHiddenStatic(Endpoint::class, "instance", null);
        }
    }

?>
