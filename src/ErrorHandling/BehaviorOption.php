<?php

    namespace ZubZet\Framework\ErrorHandling;

    class BehaviorOption {
        public const NONE = 0;
        public const EXCEPTIONS = 1;
        public const ALL = 2;

        public static function isValidOption(mixed $option): bool {
            return in_array($option, [self::NONE, self::EXCEPTIONS, self::ALL]);
        }

        /** Reads a configured value: integers like "1" are accepted, anything unrecognised shows no errors. */
        public static function fromConfig(mixed $value): int {
            $option = filter_var($value, FILTER_VALIDATE_INT);
            return false !== $option && self::isValidOption($option) ? $option : self::NONE;
        }
    }

?>
