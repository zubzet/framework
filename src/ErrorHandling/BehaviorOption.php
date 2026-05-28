<?php

    namespace ZubZet\Framework\ErrorHandling;

    class BehaviorOption {
        public const NONE = 0;
        public const EXCEPTIONS = 1;
        public const ALL = 2;

        public static function isValidOption(int $option): bool {
            return in_array($option, [self::NONE, self::EXCEPTIONS, self::ALL]);
        }
    }

?>