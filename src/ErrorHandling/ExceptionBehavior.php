<?php

    namespace ZubZet\Framework\ErrorHandling;

    use ZubZet\Framework\ErrorHandling\BehaviorOption;

    trait ExceptionBehavior {

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
                    if(error_reporting() & $severity) {
                        throw new \ErrorException($message, 0, $severity, $file, $line);
                    }
                });
                return;
            }

            // Standard Exception Handling on / off
            ini_set('display_errors', $this->showErrors);
            ini_set('display_startup_errors', $this->showErrors);
            error_reporting($this->showErrors == 1 ? E_ALL : 0);
        }

    }

?>