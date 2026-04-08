<?php

    namespace ZubZet\Framework\Logger;

    use Monolog\Handler\NullHandler;
    use Monolog\Logger;
    use ZubZet\Framework\Support\StaticCache;

    class LoggerFactory {

        private const CACHE_TYPE = 'loggers';

        /**
         * Register a fully configured Logger in the cache with the given name. 
         * This allows you to create and configure a Logger instance manually and 
         * then register it for future use.
         *
         * @param string $name The name of the logger to register
         * @param Logger $logger The fully configured Logger instance to register
         * @return Logger The registered Logger instance
         */
        public static function register(string $name, Logger $logger): Logger {
            return StaticCache::set(self::CACHE_TYPE, $name, $logger);
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
            if(StaticCache::has(self::CACHE_TYPE, $name)) return StaticCache::get(self::CACHE_TYPE, $name);

            $logger = new Logger($name);

            $enabled = config("logger_enabled", default: true);
            if(!$enabled) {
                // If logging is disabled, use a NullHandler to discard all log messages
                $logger->pushHandler(new NullHandler());
                StaticCache::set(self::CACHE_TYPE, $name, $logger);
                return $logger;
            }

            // Resolve logger type
            $type = config("logger_type", default: "database");

            // Resolve logger level
            $loggerLevel = config("logger_level", default: "debug");
            $loggerLevel = Logger::toMonologLevel($loggerLevel);

            $loggerStreamUrl = config("logger_stream_url", default: "php://stderr");

            // Find correct handler based on type
            $handler = match($type) {
                "database" => new DatabaseLogger(),
                "stream" => new StreamLogger($loggerStreamUrl),
                default => throw new \Exception("Invalid logger type: $type")
            };

            $handler->setLevel($loggerLevel);
            $logger->pushHandler($handler);

            // Cache the logger instance for future use
            StaticCache::set(self::CACHE_TYPE, $name, $logger);

            return $logger;
        }

    }

?>