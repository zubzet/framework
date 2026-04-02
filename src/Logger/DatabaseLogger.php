<?php

    namespace ZubZet\Framework\Logger;

    use Monolog\Handler\AbstractProcessingHandler;

    class DatabaseLogger extends AbstractProcessingHandler {

        public function __construct($level) {
            return parent::__construct($level);
        }

        protected function write(array $record): void {
            if(is_null(zubzet()->z_db)) return;

            model("z_logger")->log($record);
        }
    }