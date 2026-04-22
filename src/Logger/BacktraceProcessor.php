<?php

    namespace ZubZet\Framework\Logger;
    use Monolog\Processor\IntrospectionProcessor;

    class BacktraceProcessor extends IntrospectionProcessor {

        private Logger $logger;

        public function __construct($logger, $level = Logger::DEBUG, array $skipClassesPartials = [], int $skipStackFramesCount = 0) {
            $this->logger = $logger;
            return parent::__construct($level, $skipClassesPartials, $skipStackFramesCount);
        }

        public function __invoke(array $record): array {
            $record = parent::__invoke($record);
            unset($record["extra"]["callType"]);

            $record["extra"]["traceId"] = LoggerFactory::getTraceId();
            $record["extra"] = array_merge($record["extra"], $this->logger->context);

            return $record;
        }

    }

?>