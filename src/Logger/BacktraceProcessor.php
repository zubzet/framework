<?php

    namespace ZubZet\Framework\Logger;
    use Monolog\Processor\IntrospectionProcessor;

    class BacktraceProcessor extends IntrospectionProcessor {

        public function __invoke(array $record): array {
            $record = parent::__invoke($record);
            unset($record["extra"]["callType"]);

            $record["extra"]["traceId"] = LoggerFactory::getTraceId();
            return $record;
        }

    }

?>