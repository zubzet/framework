<?php

    namespace ZubZet\Framework\Logger;

use Cake\Database\Query\InsertQuery;
use Monolog\Handler\AbstractProcessingHandler;
    use Monolog\Level;
    use Monolog\LogRecord;
    use ZubZet\Framework\QueryBuilder\CanBuildQuery;

    class DatabaseLogger extends AbstractProcessingHandler {

        use CanBuildQuery;

        protected function write(LogRecord $record): void {
            if(is_null(zubzet()->z_db)) return;

            model("z_logger")->log($record);
        }
    }