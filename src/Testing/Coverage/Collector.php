<?php

    namespace ZubZet\Framework\Testing\Coverage;

    use SebastianBergmann\CodeCoverage\CodeCoverage;
    use SebastianBergmann\CodeCoverage\Driver\Selector;
    use SebastianBergmann\CodeCoverage\Filter;
    use Symfony\Component\Filesystem\Filesystem;

    /**
     * @internal
     * @codeCoverageIgnore The coverage engine itself; cannot meaningfully measure itself.
     */
    final class Collector {

        public static string $sessionLocation = ".coverage.session";
        public static string $dataDirectory = ".coverage/";
        public static string $sessionId = "";

        private static ?CodeCoverage $coverage = null;

        public static function initialize(): void {
            if(Collector::isActive()) {
                if(!self::hasDriver()) {
                    throw new \RuntimeException(
                        "Coverage session is active (" . self::$sessionLocation . " exists), " .
                        "but no PHP code coverage driver is loaded. " .
                        "Install and enable Xdebug or PCOV, or remove " . self::$sessionLocation . " to disable coverage."
                    );
                }

                $coverageFramework = filter_var(getenv('DEBUG_ZUBZET_COVERAGE_FRAMEWORK') ?: 'false', FILTER_VALIDATE_BOOLEAN);
                Collector::start($coverageFramework);

                register_shutdown_function(function() {
                    Collector::stop();
                });
            }
        }

        /** Returns true if a coverage session file exists. */
        public static function isActive(): bool {
            return file_exists(self::$sessionLocation);
        }

        /** Returns true if a PHP code coverage driver (Xdebug or PCOV) is loaded. */
        public static function hasDriver(): bool {
            return extension_loaded('xdebug') || extension_loaded('pcov');
        }

        /** Returns the session ID, reading it from disk on first call. */
        public static function getSessionId(): string {
            if(empty(self::$sessionId)) {
                self::$sessionId = trim(file_get_contents(self::$sessionLocation) ?: "");
            }
            return self::$sessionId;
        }

        /** Initializes a new CodeCoverage instance and starts collecting for the current request. */
        public static function start(bool $testFramework = false): void {
            $filter = new Filter();
            $filter->includeDirectory("./app");
            $filter->excludeDirectory("./app/Database");

            // Include the framework source for coverage if we're running
            // within a test framework context,
            // to allow measuring coverage of framework code during tests
            if($testFramework) {
                $filter->includeDirectory("../vendor/zubzet/framework/src");
            }

            self::$coverage = new CodeCoverage(
                (new Selector)->forLineCoverage($filter),
                $filter
            );

            self::$coverage->start(uniqid('request_'));
        }

        /** Stops collection and serializes the coverage data to disk. */
        public static function stop(): void {
            if(is_null(self::$coverage)) return;

            self::$coverage->stop();

            $dir = self::$dataDirectory . self::getSessionId() . '/';
            if(!is_dir($dir)) mkdir($dir, 0755, true);

            file_put_contents($dir . uniqid() . '.cov', serialize(self::$coverage));
            self::$coverage = null;
        }

        /** Deserializes and merges all collected .cov files into a single CodeCoverage instance. */
        public static function merge(): ?CodeCoverage {
            $files = glob(self::$dataDirectory . self::getSessionId() . '/*.cov');

            $merged = null;
            foreach($files as $file) {
                /** @var CodeCoverage $cov */
                $cov = unserialize(file_get_contents($file));

                if(is_null($merged)) {
                    $merged = $cov;
                    continue;
                }

                $merged->merge($cov);
            }

            return $merged;
        }

        public static function cleanup(): void {
            // Remove all coverage data files for the current session
            $dir = self::$dataDirectory . self::getSessionId() . '/';
            (new Filesystem())->remove($dir);

            // Remove current session file to allow starting a new session
            $sessionLocation = Collector::$sessionLocation;
            (new Filesystem())->remove($sessionLocation);

            self::$sessionId = "";
        }
    }
