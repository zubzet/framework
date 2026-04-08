<?php

    namespace ZubZet\Framework\Logger;

    use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

    class JsonFormatter extends BaseJsonFormatter {
        public function format(array $record): string {
            model("z_logger")->appendEnvironment($record);
            return parent::format($record);
        }
    }

?>