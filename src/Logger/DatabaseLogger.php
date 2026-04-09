<?php

    namespace ZubZet\Framework\Logger;

    use Monolog\Handler\AbstractProcessingHandler;

    class DatabaseLogger extends AbstractProcessingHandler {

        protected function write(array $record): void {
            // There might be logs before a database connection or in case of a connection failure
            // Which could still be caught using a different logger
            if(is_null(db(allowUnsetConnection: true))) return;

            // Log the record using the z_logger model, which handles normalization and encoding
            model("z_logger")->log($record);
        }
    }

?>