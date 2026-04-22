<?php
    namespace ZubZet\Framework\Logger;

    use Monolog\Logger as MonologLogger;

    class Logger extends MonologLogger{

        public const APP = "app";
        public const ZUBZET = "zubzet";

        public array $context = [];

        public function contextAdd(array $context): Logger {
            $this->context = array_merge($this->context, $context);
            return $this;
        }

        public function contextMergeFrom(string $logger = self::APP): Logger {
            if(empty($logger)) throw new \InvalidArgumentException("Logger name cannot be empty.");

            $loggerInstance = LoggerFactory::getLogger($logger);
            if(is_null($loggerInstance)) throw new \Exception("Logger with name '$logger' not found in cache.");

            $this->context = array_merge($this->context, $loggerInstance->context);
            return $this;
        }

        public function contextInspect(callable $callback): Logger {
            $this->context = $callback($this->context);
            return $this;
        }

        public function contextClear(): Logger{
            $this->context = [];
            return $this;
        }

        public static function getTraceId(): string {
            return LoggerFactory::getTraceId();
        }

        public static function setTraceId(string $traceId): void {
            LoggerFactory::$traceId = $traceId;
        }
    }

?>
