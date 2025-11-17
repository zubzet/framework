<?php

    namespace ZubZet\Framework\Core;

    class FunctionConflictResolution {
        public static function require(string $name): void {
            if(!function_exists($name)) return;
            self::resolve([
                "The function '$name' is already defined, but is required by ZubZet",
                "There might be a second package, which includes a function with the same name.",
                "Please remove the source of the conflict to proceed.",
            ]);
        }

        public static function resolve(string|array $message): void {
            if(is_array($message)) {
                $message = implode("\n", $message);
            }

            throw new \RuntimeException($message);
        }
    }

?>