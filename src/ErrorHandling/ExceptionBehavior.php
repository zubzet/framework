<?php

    namespace ZubZet\Framework\ErrorHandling;

    use Whoops\Handler\PlainTextHandler;
    use Whoops\Handler\PrettyPageHandler;
    use Whoops\Run;
    use ZubZet\Framework\Core\CanManageCache;
    use ZubZet\Framework\ErrorHandling\BehaviorOption;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Logger\LoggerFactory;

    trait ExceptionBehavior {

        use CanManageCache;

        /**
         * Maps a PHP error severity bitmask constant onto a PSR-3 log level and
         * a stable LogEventType string. The event type is what lets operators
         * filter by `WHERE message = LogEventType::DEPRECATION` while the level
         * carries the actual severity.
         *
         * @return array{0: string, 1: string} [monologLevelMethod, logEventType]
         */
        private static function classifyErrorSeverity(int $severity): array {
            return match(true) {
                // Error
                (bool) ($severity & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) => ["error", LogEventType::ERROR],

                // Parsing
                (bool) ($severity & E_PARSE) => ["critical", LogEventType::PARSE],

                // Warning
                (bool) ($severity & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING)) => ["warning", LogEventType::WARNING],

                // Deprecation
                (bool) ($severity & (E_DEPRECATED | E_USER_DEPRECATED)) => ["notice", LogEventType::DEPRECATION],

                // Notice
                (bool) ($severity & (E_NOTICE | E_USER_NOTICE)) => ["notice", LogEventType::NOTICE],

                // E_STRICT is gone on PHP >= 8.4 but the bit (2048) may still be set by legacy user land code
                (bool) ($severity & 2048) => ["debug", LogEventType::STRICT],

                default => ["warning", LogEventType::ERROR],
            };
        }

        /**
         * Logs a PHP error on the ZubZet channel using the classified severity.
         * Swallows any logging failure so that the error handler stays a pure sink
         * — a broken logger (e.g. the log table not yet existing at bootstrap)
         * must not replace the original error with an unrelated one.
         */
        private static function logError(int $severity, string $message, string $file, int $line): void {
            [$level, $eventType] = self::classifyErrorSeverity($severity);
            try {
                logger(Logger::ZUBZET)->$level($eventType, [
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                ]);
            } catch(\Throwable) {
                // Logger unavailable; drop the entry so error handling continues.
            }
        }

        /**
         * Updates the error handling state
         * Options are defined in the BehaviorOption class
         * @param int|null $state
         */
        public function setExceptionBehavior(?int $state = null): void {
            $this->registerWhoopsHandler();

            // State or attribute check
            if(!is_null($state)) {
                $this->showErrors = $state;
            }

            if(!BehaviorOption::isValidOption($this->showErrors)) {
                throw new \InvalidArgumentException("Invalid exception behavior option: " . $this->showErrors);
            }

            // Custom error handler that converts all errors to exceptions (including warnings)
            if(BehaviorOption::ALL == $this->showErrors) {
                set_error_handler(function($severity, $message, $file, $line) {
                    // Respect `@` suppression and error_reporting level.
                    if(!(error_reporting() & $severity)) return false;

                    self::logError($severity, $message, $file, $line);
                    throw new \ErrorException($message, 0, $severity, $file, $line);
                });
                self::registerExceptionHandler();
                return;
            }

            // NONE / EXCEPTIONS mode
            set_error_handler(function($severity, $message, $file, $line) {
                // Respect `@` suppression and error_reporting level.
                if(!(error_reporting() & $severity)) return false;

                self::logError($severity, $message, $file, $line);
                return false; // PHP default behavior
            });

            self::registerExceptionHandler();

            // Standard Exception Handling on / off
            ini_set('display_errors', $this->showErrors);
            ini_set('display_startup_errors', $this->showErrors);
            error_reporting($this->showErrors == 1 ? E_ALL : 0);
        }

        /**
         * Logs uncaught throwables on the ZubZet channel and re-throws so the
         * next layer (PHP's default fatal display, or a replacement such as a
         * Whoops-style error page) can render.
         *
         * Note: we deliberately do NOT call `restore_exception_handler()` before
         * the re-throw. On PHP >= 8.3 that combination makes PHP re-invoke this
         * handler with the re-thrown throwable and recurse until
         * `zend.max_allowed_stack_size` trips. Plain `throw $e` from inside the
         * handler lets PHP dispatch to its default path exactly once.
         */
        private static function registerExceptionHandler(): void {
            set_exception_handler(function(\Throwable $e) {
                LoggerFactory::$uncaughtException = true;
                try {
                    logger(Logger::ZUBZET)->error(LogEventType::EXCEPTION, [
                        'class' => \get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                } catch(\Throwable) {
                    // Swallow logging failures — surfacing the original $e matters more.
                }
                throw $e;
            });
        }

        private function registerWhoopsHandler(): void {
            if(config("execution_type", default: "prod") !== "test") return;
            $whoops = new Run();

            match(false) {
                true => $whoops->pushHandler(new PlainTextHandler()),
                false => $this->handlePrettyPageHandler($whoops),
            };

            $whoops->register();
        }

        private function handlePrettyPageHandler(Run $whoops): void {
            $handler = new PrettyPageHandler();
            $containerAppPath = "/var/www/html/";

            $pwd = $this->getCache("pwd");
            if(!is_null($pwd) && !empty($pwd)) {
                $hostAppPath = rtrim($pwd, "/") . "/";

                $handler->setEditor(function($file, $line) use ($containerAppPath, $hostAppPath) {
                    if(!str_starts_with($file, $containerAppPath)) return null;
                    $relative = substr($file, \strlen($containerAppPath));
                    return "vscode://file/{$hostAppPath}{$relative}:{$line}";
                });
            }

            // Where am i
            $handler->setApplicationPaths([$containerAppPath]);

            // Hide environment variables
            foreach(array_keys($_ENV) as $key) {
                $handler->hideSuperglobalKey('_ENV', $key);
            }

            $whoops->pushHandler($handler);
        }

    }

?>
