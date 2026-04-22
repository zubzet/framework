<?php

    namespace ZubZet\Framework\ErrorHandling;

    use ZubZet\Framework\ErrorHandling\BehaviorOption;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Logger\LoggerFactory;

    trait ExceptionBehavior {

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
         */
        private static function logError(int $severity, string $message, string $file, int $line): void {
            [$level, $eventType] = self::classifyErrorSeverity($severity);
            logger(Logger::ZUBZET)->$level($eventType, [
                'message' => $message,
                'file' => $file,
                'line' => $line,
            ]);
        }

        /**
         * Updates the error handling state
         * Options are defined in the BehaviorOption class
         * @param int|null $state
         */
        public function setExceptionBehavior(?int $state = null): void {
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
         * Logs uncaught throwables on the ZubZet channel, then restores the
         * previous exception handler and rethrows so PHP's default display
         * (or a future Whoops-style handler) still renders the error page.
         */
        private static function registerExceptionHandler(): void {
            set_exception_handler(function(\Throwable $e) {
                LoggerFactory::$uncaughtException = true;
                logger(Logger::ZUBZET)->error(LogEventType::EXCEPTION, [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                restore_exception_handler();
                throw $e;
            });
        }

    }

?>
