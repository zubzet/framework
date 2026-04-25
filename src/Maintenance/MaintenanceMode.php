<?php

    namespace ZubZet\Framework\Maintenance;

    /**
     * @internal
     */
    final class MaintenanceMode {
        public const DISABLED = "disabled";
        public const SOFT = "soft";
        public const ENABLED = "enabled";
        public const FULL = "full";

        public static function isValid(mixed $value): bool {
            return in_array($value, [self::DISABLED, self::SOFT, self::ENABLED, self::FULL], true);
        }
    }
?>
