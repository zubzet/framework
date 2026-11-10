<?php

    namespace Tests;

    use PHPUnit\Framework\TestCase as BaseTestCase;
    use ReflectionClass;
    use ZubZet\Framework\Core\Constants;
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
         */
        protected function input(): State {
            $input = new State();
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

        /** An empty directory with a trailing slash, removed after the test. */
        protected function temporaryDirectory(): string {
            $directory = sys_get_temp_dir() . "/zubzet-unit-" . uniqid() . "/";
            mkdir($directory);

            $this->temporaryDirectories[] = $directory;
            return $directory;
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
            $reflection->setAccessible(true);

            return $reflection->invoke(is_object($target) ? $target : null, ...$arguments);
        }

        // RuntimeCache and the classes extending it share one private store
        private function clearRuntimeCaches(): void {
            $store = (new ReflectionClass(RuntimeCache::class))->getProperty("cache");
            $store->setAccessible(true);
            $store->setValue(null, []);
        }
    }

?>
