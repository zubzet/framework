<?php

    namespace ZubZet\Framework\Maintenance;

    class MaintenanceMode {
        public const DISABLED = "disabled";
        public const SOFT = 'soft';
        public const ENABLED = "enabled";

        public static function parse(mixed $value): string|bool {
            return match(true) {
                $value == "disabled" => self::DISABLED,
                $value == "enabled" => self::ENABLED,
                $value == 'soft' => self::SOFT,
                default => throw new \InvalidArgumentException("Invalid maintenance mode configuration: \"{$value}\". Allowed values are \"disabled\", \"enabled\", or \"soft\".")
            };
        }

        public static function isValid(mixed $value): bool {
            return in_array($value, [self::DISABLED, self::SOFT, self::ENABLED], true);
        }
    }

?>