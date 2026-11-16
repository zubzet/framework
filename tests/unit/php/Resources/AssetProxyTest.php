<?php

    namespace Tests\Resources;

    use ReflectionClass;
    use Tests\TestCase;
    use ZubZet\Framework\Resources\AssetProxy;

    /**
     * serve() sets the response code, which PHP refuses once the test runner has
     * printed anything. In a process of its own nothing was printed before the test.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    class AssetProxyTest extends TestCase {

        private const CONTENT = "A file inside the assets directory";

        protected function setUp(): void {
            $this->bootFramework();
            $this->request();
        }

        // realpath() throws a ValueError for a null byte, which used to leave serve() as an error
        public function testAnswersAPathWithANullByteAsNotFound(): void {
            $output = $this->serve("assets.txt\0.png");

            $this->assertSame(404, http_response_code());
            $this->assertStringStartsWith("Asset not found: ", $output);
            $this->assertStringNotContainsString(self::CONTENT, $output);
        }

        public function testAnswersAPathThatEscapesTheSourceAsNotFound(): void {
            $output = $this->serve("../outside.txt");

            $this->assertSame(404, http_response_code());
            $this->assertSame("Asset not found: ../outside.txt", $output);
        }

        public function testAnswersAMissingFileAsNotFound(): void {
            $this->assertSame("Asset not found: missing.txt", $this->serve("missing.txt"));
            $this->assertSame(404, http_response_code());
        }

        // What serve() prints for the path, with one source that holds assets.txt
        private function serve(string $assetPath): string {
            $base = $this->temporaryDirectory();
            mkdir($base . "assets");
            file_put_contents($base . "assets/assets.txt", self::CONTENT);
            file_put_contents($base . "outside.txt", "A file outside the assets directory");

            // The constructor registers the sources of an installed application
            $proxy = (new ReflectionClass(AssetProxy::class))->newInstanceWithoutConstructor();
            $proxy->registerWebRootSource($base . "assets/");

            ob_start();
            try {
                $proxy->serve($assetPath);
            } finally {
                $output = ob_get_clean();
            }

            return $output;
        }
    }

?>
