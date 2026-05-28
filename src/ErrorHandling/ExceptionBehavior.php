<?php

    namespace ZubZet\Framework\ErrorHandling;

    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Support\StaticCache;
    use ZubZet\Framework\Logger\LoggerFactory;
    use ZubZet\Framework\ErrorHandling\WhoopsHandler;
    use ZubZet\Framework\ErrorHandling\BehaviorOption;

    trait ExceptionBehavior {

        /**
         * @return array{0: string, 1: string} [monologLevelMethod, logEventType]
         */
        private static function classifyErrorSeverity(int $severity): array {
            return match(true) {
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

        // Logs a PHP error; swallows logger failures so they can't mask the original.
        private static function logError(int $severity, string $message, string $file, int $line): void {
            [$level, $eventType] = self::classifyErrorSeverity($severity);
            try {
                logger(Logger::ZUBZET)->$level($eventType, [
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                ]);
            } catch(\Throwable) {}
        }

        public function setExceptionBehavior(?int $state = null): void {
            $this->registerWhoopsHandler();

            if(!is_null($state)) {
                $this->showErrors = $state;
            }

            if(!BehaviorOption::isValidOption($this->showErrors)) {
                throw new \InvalidArgumentException("Invalid exception behavior option: " . $this->showErrors);
            }

            // ALL: promote every error (including warnings) to an exception.
            if(BehaviorOption::ALL == $this->showErrors) {
                set_error_handler(function($severity, $message, $file, $line) {
                    if(!(error_reporting() & $severity)) return false; // respects `@` and error_reporting level
                    self::logError($severity, $message, $file, $line);
                    throw new \ErrorException($message, 0, $severity, $file, $line);
                });
                self::registerExceptionHandler();
                return;
            }

            // NONE / EXCEPTIONS: log, then fall through to PHP's default handler.
            set_error_handler(function($severity, $message, $file, $line) {
                if(!(error_reporting() & $severity)) return false;
                self::logError($severity, $message, $file, $line);
                return false;
            });
            self::registerExceptionHandler();

            ini_set('display_errors', $this->showErrors);
            ini_set('display_startup_errors', $this->showErrors);
            error_reporting($this->showErrors == 1 ? E_ALL : 0);
        }

        /**
         * Logs uncaught throwables and dispatches rendering. When Whoops is
         * active we invoke it directly so it receives the full throwable —
         * re-throwing hands off to PHP's default fatal display, after which
         * Whoops only sees the tail via `handleShutdown()` and loses every
         * vendor/framework frame.
         *
         * Do NOT pair this with `restore_exception_handler()`: on PHP >= 8.3
         * the combination recurses on the re-thrown throwable until
         * `zend.max_allowed_stack_size` trips.
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
                    // Logger failure must not mask the original $e.
                }

                $whoopsHandler = StaticCache::getOrNull("handler", "whoops");
                if(!is_null($whoopsHandler) && !empty($whoopsHandler->run->getHandlers())) {
                    $whoopsHandler->run->handleException($e);
                    return;
                }

                throw $e;
            });
        }

        private function registerWhoopsHandler(): void {
            StaticCache::set("handler", "whoops", new WhoopsHandler);
        }

    }

?>
