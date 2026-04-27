<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar;

    use ZubZet\Framework\Core\Model;

    use Monolog\Logger;

    trait CanCollect {

        public static function collectQuery(Model $model, string $sql, float $durationSeconds, int $rowCount, array $values): void {
            self::collect("queries", "addQuery", func_get_args());
        }

        public static function collectTemplate(string $name, array $data, ?string $type = null, ?string $path = null): void {
            self::collect("templates", "addTemplate", func_get_args());
        }

        public static function collectLogger(Logger $logger): void {
            self::collect("monolog", "addLogger", func_get_args());
        }

    }
?>