<?php

    namespace ZubZet\Framework\Core;

    class FunctionConflictResolution {
        /**
         * @internal Check for a function conflict and, when no conflict exists, run the
         * provided callable which contains the actual function declaration.
         *
         * @param string $name Name of the function
         * @param callable $declaration Callable that declares the function when executed
         */
        public static function requireAndThen(string $name, callable $declaration): void {
            if(function_exists($name)) {
                self::resolve(message: [
                    "The function '$name' is already defined, but is required by ZubZet",
                    "There might be a second package, which includes a function with the same name.",
                    "Please remove the source of the conflict to proceed.",
                ]);
            }

            // Execute the declaration (closure should declare the function)
            $declaration();
        }

        public static function resolve(string|array $message): void {
            if(is_array($message)) {
                $message = implode("\n", $message);
            }

            throw new \RuntimeException($message);
        }
    }

?>