<?php

    namespace Tests\Testing\Coverage;

    use PHPUnit\Framework\TestCase;
    use ZubZet\Framework\Testing\Coverage\Collector;

    class CollectorCleanupTest extends TestCase {

        private const SESSION_ID = "session123";

        private string $root;
        private array $original;

        protected function setUp(): void {
            $this->original = [
                "sessionLocation" => Collector::$sessionLocation,
                "dataDirectory" => Collector::$dataDirectory,
                "sessionId" => Collector::$sessionId,
            ];

            $this->root = sys_get_temp_dir() . "/zubzet-coverage-" . uniqid() . "/";
            mkdir($this->root . ".coverage", 0755, true);

            Collector::$sessionLocation = $this->root . ".coverage.session";
            Collector::$dataDirectory = $this->root . ".coverage/";
            Collector::$sessionId = "";
        }

        protected function tearDown(): void {
            Collector::$sessionLocation = $this->original["sessionLocation"];
            Collector::$dataDirectory = $this->original["dataDirectory"];
            Collector::$sessionId = $this->original["sessionId"];

            $this->removeTree($this->root);
        }

        public function testRemovesTheSessionDataAndTheSessionFile(): void {
            $sessionDir = $this->startSession();
            file_put_contents($sessionDir . "a.cov", "a");
            file_put_contents($sessionDir . "b.cov", "b");
            file_put_contents($sessionDir . ".hidden", "c");

            Collector::cleanup();

            $this->assertDirectoryDoesNotExist($sessionDir);
            $this->assertFileDoesNotExist(Collector::$sessionLocation);
            $this->assertFalse(Collector::isActive());
            $this->assertSame("", Collector::$sessionId);
        }

        public function testKeepsTheReportAndOtherSessions(): void {
            $sessionDir = $this->startSession();
            file_put_contents($sessionDir . "a.cov", "a");

            mkdir(Collector::$dataDirectory . "report");
            file_put_contents(Collector::$dataDirectory . "report/index.html", "report");
            mkdir(Collector::$dataDirectory . "other");
            file_put_contents(Collector::$dataDirectory . "other/a.cov", "a");

            Collector::cleanup();

            $this->assertFileExists(Collector::$dataDirectory . "report/index.html");
            $this->assertFileExists(Collector::$dataDirectory . "other/a.cov");
        }

        public function testSessionWithoutCollectedDataOnlyLosesItsSessionFile(): void {
            file_put_contents(Collector::$sessionLocation, self::SESSION_ID);

            Collector::cleanup();

            $this->assertFileDoesNotExist(Collector::$sessionLocation);
            $this->assertDirectoryExists(Collector::$dataDirectory);
        }

        public function testWithoutASessionNothingIsRemoved(): void {
            file_put_contents(Collector::$dataDirectory . "stray.cov", "a");

            Collector::cleanup();

            $this->assertFileExists(Collector::$dataDirectory . "stray.cov");
        }

        public function testAnEmptySessionFileDoesNotEmptyTheDataDirectory(): void {
            file_put_contents(Collector::$sessionLocation, "");
            file_put_contents(Collector::$dataDirectory . "stray.cov", "a");

            Collector::cleanup();

            $this->assertFileExists(Collector::$dataDirectory . "stray.cov");
            $this->assertFileDoesNotExist(Collector::$sessionLocation);
        }

        // The state testing:coverage:start leaves behind, returns the directory the requests write into
        private function startSession(): string {
            file_put_contents(Collector::$sessionLocation, self::SESSION_ID);

            $sessionDir = Collector::$dataDirectory . self::SESSION_ID . "/";
            mkdir($sessionDir);

            return $sessionDir;
        }

        private function removeTree(string $dir): void {
            foreach(array_diff(scandir($dir), [".", ".."]) as $entry) {
                $path = $dir . $entry;

                if(is_dir($path)) {
                    $this->removeTree($path . "/");
                    continue;
                }

                unlink($path);
            }

            rmdir($dir);
        }
    }

?>
