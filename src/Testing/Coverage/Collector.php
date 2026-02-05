<?php

    namespace ZubZet\Framework\Testing\Coverage;

    use SebastianBergmann\CodeCoverage\CodeCoverage;
    use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
    use SebastianBergmann\CodeCoverage\Filter;

    /**
     * @internal
     */
    final class Collector {

        public static string $sessionLocation = ".coverage.session";
        public static string $sessionId;

        public static function getSessionId(): string {
            
        }
    }
