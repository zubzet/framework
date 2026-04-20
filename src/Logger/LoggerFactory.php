<?php

    namespace ZubZet\Framework\Logger;

    use Psr\Log\LoggerInterface;
    use Monolog\Handler\NullHandler;
    use ZubZet\Framework\Logger\BacktraceProcessor as LoggerBacktraceProcessor;
    use ZubZet\Framework\Support\StaticCache;

    class LoggerFactory {

        // Will be created in getTraceId()
        public static ?string $traceId = null;

        public static bool $isLogging = false;

        private const CACHE_KEY = 'logger';

        public const ZUBZET = "zubzet";

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
            if(is_null(self::$traceId)) {
                // Generate a random 32-character hexadecimal string
                self::$traceId = bin2hex(random_bytes(16));
            }

            return self::$traceId;
        }

        public static function setTraceId(string $traceId): void {
            self::$traceId = $traceId;
        }

        public static function getLogger(?string $name): ?Logger {
            return StaticCache::get(self::CACHE_KEY, $name ?? "app");
        }

        public static function handleSlowRequest() {
            $start = microtime(true);
            register_shutdown_function(function() use ($start) {
                $duration = (microtime(true) - $start) * 1000;
                $threshold = config("logger_slow_request_ms", default: null);
                if(is_null($threshold)) return;
                if($duration >= $threshold) {
                    logger()->warning("Slow request", [
                        'duration_ms' => round($duration, 2),
                        'uri' => request()->input->SERVER['REQUEST_URI'] ?? '/',
                    ]);
                }
            });
        }

    }

?>