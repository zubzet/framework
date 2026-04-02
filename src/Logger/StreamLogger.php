<?php

namespace ZubZet\Framework\Logger;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class StreamLogger extends StreamHandler {

    public function __construct($stream, $level = Logger::DEBUG, bool $bubble = true, ?int $filePermission = null, bool $useLocking = false, $fileOpenMode = 'a') {
        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking, $fileOpenMode);

        $this->setFormatter(new class extends JsonFormatter {
            public function format(array $record): string {
                model("z_logger")->appendEnvironment($record);
                return $this->toJson($record) . "\n";
            }
        });
    }

}