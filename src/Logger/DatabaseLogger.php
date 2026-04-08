<?php

    namespace ZubZet\Framework\Logger;

    use Monolog\Handler\AbstractProcessingHandler;

    class DatabaseLogger extends AbstractProcessingHandler {

        protected function write(array $record): void {
            if(is_null(zubzet()->z_db)) return;

            // Log the record using the z_logger model, which handles normalization and encoding
            model("z_logger")->log($record);
        }
    }