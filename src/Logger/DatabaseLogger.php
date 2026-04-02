<?php

    namespace ZubZet\Framework\Logger;

    use Monolog\Handler\AbstractProcessingHandler;
    use ZubZet\Framework\QueryBuilder\CanBuildQuery;

    class DatabaseLogger extends AbstractProcessingHandler {
        protected function write(array $record): void {
            if(is_null(zubzet()->z_db)) return;

            model("z_logger")->log($record);
        }
    }