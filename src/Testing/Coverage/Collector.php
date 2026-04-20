<?php

    namespace ZubZet\Framework\Testing\Coverage;

    use SebastianBergmann\CodeCoverage\CodeCoverage;
    use SebastianBergmann\CodeCoverage\Driver\Selector;
    use SebastianBergmann\CodeCoverage\Filter;

    /**
     * @internal
     */
    final class Collector {

        public static string $sessionLocation = ".coverage.session";
        public static string $dataDirectory = ".coverage/";
        public static string $sessionId = "";

        private static ?CodeCoverage $coverage = null;

        /** Returns true if a coverage session file exists. */
        public static function isActive(): bool {
            return file_exists(self::$sessionLocation);
        }

        /** Returns the session ID, reading it from disk on first call. */
        public static function getSessionId(): string {
            if(self::$sessionId === "") {
                self::$sessionId = trim(file_get_contents(self::$sessionLocation) ?: "");
            }
            return self::$sessionId;
        }

        /** Initializes a new CodeCoverage instance and starts collecting for the current request. */
        public static function start(bool $testFramework = false): void {
            $filter = new Filter();
            $filter->includeDirectory("./app");
            $filter->excludeDirectory("./app/Database");
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

        /** Removes all .cov files and the session directory from disk. */
        public static function cleanup(): void {
            $dir = self::$dataDirectory . self::getSessionId() . '/';
            foreach(glob("{$dir}*.cov") ?: [] as $file) unlink($file);
            if(is_dir($dir)) rmdir($dir);
        }
    }
