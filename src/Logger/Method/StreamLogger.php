<?php

    namespace ZubZet\Framework\Logger\Method;

    use Monolog\Handler\StreamHandler;
    use ZubZet\Framework\Logger\JsonFormatter;

    class StreamLogger extends StreamHandler {

        public function __construct($stream, ...$args) {
            parent::__construct($stream, ...$args);

            // Use a custom formatter that appends environment context
            // to each log record before encoding as JSON
            $this->setFormatter(new JsonFormatter());
        }

    }

?>
