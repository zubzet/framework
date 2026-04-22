<?php

    namespace ZubZet\Framework\Logger;

    use ZubZet\Framework\Support\StaticCache;
    use ZubZet\Framework\Logger\Method\StreamLogger;
    use ZubZet\Framework\Logger\Method\DatabaseLogger;
    use ZubZet\Framework\Logger\BacktraceProcessor as LoggerBacktraceProcessor;

    use Psr\Log\LoggerInterface;
    use Monolog\Handler\NullHandler;

    class LoggerFactory {

        /** @internal Lazily initialized on first access via getTraceId(); may be pre-set by Logger::setTraceId(). */
        public static string $traceId;

        /** @internal Set by the uncaught-exception handler; read by the slow-request shutdown hook. */
        public static bool $uncaughtException = false;

        private const CACHE_KEY = 'logger';

        /**
         * Register a fully configured Logger in the cache with the given name.
         * This allows you to create and configure a Logger instance manually and
         * then register it for future use.
         *
         * @param string $name The name of the logger to register
         * @param Logger|LoggerInterface $logger The fully configured Logger instance to register
         * @return Logger The registered Logger instance
         */
        public static function register(string $name, Logger|LoggerInterface $logger): Logger {
            return StaticCache::set(self::CACHE_KEY, $name, $logger);
        }

        /**
         * Get an existing logger from the cache or create a new one if it doesn't exist.
         * This uses the configuration settings to determine the type and level of the logger, and caches it for future use.
         *
         * @param string $name The name of the logger to create or retrieve
         * @throws \Exception if the logger type is invalid
         * @return Logger The logger instance
         */
        public static function getOrCreateLogger(string $name): Logger {
            // Check if the logger already exists in the cache
            if(StaticCache::has(self::CACHE_KEY, $name)) {
                return StaticCache::get(self::CACHE_KEY, $name);
            }

            $logger = new Logger($name);

            $enabled = config("logger_enabled", default: true);
            if(!$enabled) {
                // If logging is disabled, use a NullHandler to discard all log messages
                $logger->pushHandler(new NullHandler());
                return StaticCache::set(self::CACHE_KEY, $name, $logger);
            }

            // Resolve logger type
            $type = config("logger_type", default: "database");

            // Resolve logger level
            $loggerLevel = config("logger_level", default: "notice");
            $loggerLevel = Logger::toMonologLevel($loggerLevel);

            $loggerStreamUrl = config("logger_stream_url", default: "php://stderr");

            // Find correct handler based on type
            $handler = match($type) {
                "database" => new DatabaseLogger(),
                "stream" => new StreamLogger($loggerStreamUrl),
                default => throw new \InvalidArgumentException("Invalid logger type: $type, Use database or stream in your config")
            };

            $handler->setLevel($loggerLevel);
            $logger->pushHandler($handler);
            $logger->pushProcessor(new LoggerBacktraceProcessor($logger, $loggerLevel));

            // Cache the logger instance for future use
            return StaticCache::set(self::CACHE_KEY, $name, $logger);
        }

        public static function getTraceId(): string {
            if(!isset(self::$traceId)) {
                // Generate a random 32-character hexadecimal string
                self::$traceId = bin2hex(random_bytes(16));
            }

            return self::$traceId;
        }

        public static function getLogger(string $name): ?Logger {
            if(!StaticCache::has(self::CACHE_KEY, $name)) return null;
            return StaticCache::get(self::CACHE_KEY, $name);
        }

        public static function handleSlowRequest(): void {
            register_shutdown_function(function() {
                // Uncaught exceptions are logged by the exception handler; don't double-count.
                if(self::$uncaughtException) return;

                $threshold = config("logger_slow_request_ms", default: 1000);
                if(!is_numeric($threshold) || $threshold < 0) return;

                // Prefer PHP's own request-entry timestamp so bootstrap time is counted too.
                $start = request()->input->SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
                $duration = (microtime(true) - $start) * 1000;
                if($duration < $threshold) return;

                try {
                    $uri = implode('/', request()->getUrlParts());
                } catch(\Throwable) {
                    $uri = null;
                }

                logger(Logger::ZUBZET)->warning(LogEventType::SLOW_REQUEST, [
                    'duration_ms' => round($duration, 2),
                    'uri' => $uri,
                ]);
            });
        }

    }

?>
