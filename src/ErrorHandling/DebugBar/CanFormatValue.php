<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar;

    trait CanFormatValue {

        private function formatValue(mixed $value): string {
            return match(true) {
                \is_string($value) => $value,

                \is_scalar($value), $value === null => var_export($value, true),

                default => json_encode(
                    $value,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ),
            };
        }

    }

?>
