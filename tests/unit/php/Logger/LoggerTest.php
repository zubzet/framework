<?php

    namespace Tests\Logger;

    use InvalidArgumentException;
    use Monolog\Handler\TestHandler;
    use Tests\TestCase;
    use ZubZet\Framework\Logger\Logger;

    class LoggerTest extends TestCase {

        private const TRACE_ID_FORMAT = '/^[0-9a-f]{32}$/';

        private const ERROR_EMPTY_NAME = "Logger name cannot be empty.";
        private const ERROR_UNKNOWN_LOGGER = "Logger with name 'does-not-exist' not found in cache.";

        protected function setUp(): void {
            $this->bootFramework(["logger_type" => "database", "logger_level" => "debug"]);
        }

        // TraceId

        public function testShouldAssignTheSameTraceIdToEveryLogWithinASingleRequest(): void {
            $records = $this->captureLogs(Logger::APP);

            logger()->info("first log", ["seq" => 1]);
            logger()->info("second log", ["seq" => 2]);

            $this->assertCount(2, $records->getRecords());
            $traceId = $records->getRecords()[0]["extra"]["traceId"];
            $this->assertIsString($traceId);
            $this->assertNotSame("", $traceId);
            $this->assertSame($traceId, $records->getRecords()[1]["extra"]["traceId"]);
        }

        public function testTheTraceIdIsSharedBetweenLoggers(): void {
            $app = $this->captureLogs(Logger::APP);
            $framework = $this->captureLogs(Logger::ZUBZET);

            logger()->info("from the app");
            logger(Logger::ZUBZET)->info("from the framework");

            $this->assertSame(
                $app->getRecords()[0]["extra"]["traceId"],
                $framework->getRecords()[0]["extra"]["traceId"],
            );
        }

        /**
         * The trace id is a static that cannot be reset, so the tests that depend
         * on its first value or change it run in a process of their own.
         *
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function testShouldExposeTheCurrentTraceIdViaLoggerGetTraceId(): void {
            $records = $this->captureLogs(Logger::APP);

            logger()->info("with trace");
            $returned = Logger::getTraceId();

            $this->assertMatchesRegularExpression(self::TRACE_ID_FORMAT, $returned);
            $this->assertCount(1, $records->getRecords());
            $this->assertSame($returned, $records->getRecords()[0]["extra"]["traceId"]);
            $this->assertSame($returned, Logger::getTraceId());
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function testShouldHonorLoggerSetTraceId(): void {
            $records = $this->captureLogs(Logger::APP);
            $custom = str_repeat("a", 32);

            Logger::setTraceId($custom);
            logger()->info("after setTraceId");

            $this->assertCount(1, $records->getRecords());
            $this->assertSame($custom, $records->getRecords()[0]["extra"]["traceId"]);
            $this->assertSame($custom, Logger::getTraceId());
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function testSetTraceIdReplacesAnIdThatWasAlreadyGenerated(): void {
            $records = $this->captureLogs(Logger::APP);

            logger()->info("before");
            Logger::setTraceId("custom-trace");
            logger()->info("after");

            $this->assertNotSame("custom-trace", $records->getRecords()[0]["extra"]["traceId"]);
            $this->assertSame("custom-trace", $records->getRecords()[1]["extra"]["traceId"]);
        }

        // Context

        public function testShouldCarryAddedContextForwardAcrossLogCallsOnTheSameLogger(): void {
            $records = $this->captureLogs("ctx");

            logger("ctx")->contextAdd(["x" => 1])->info("first");
            logger("ctx")->info("second");

            $this->assertCount(2, $records->getRecords());
            foreach($records->getRecords() as $record) {
                $this->assertSame(1, $record["extra"]["x"]);
            }
        }

        public function testContextAddKeepsEarlierKeysAndLetsLaterValuesWin(): void {
            $records = $this->captureLogs("ctx");

            logger("ctx")->contextAdd(["x" => 1, "keep" => "kept"])->contextAdd(["x" => 2])->info("merged");

            $extra = $records->getRecords()[0]["extra"];
            $this->assertSame(2, $extra["x"]);
            $this->assertSame("kept", $extra["keep"]);
        }

        public function testContextOfOneLoggerDoesNotLeakIntoAnotherLogger(): void {
            $other = $this->captureLogs("other");
            logger("ctx")->contextAdd(["x" => 1]);

            logger("other")->info("unrelated");

            $this->assertArrayNotHasKey("x", $other->getRecords()[0]["extra"]);
        }

        public function testShouldLetContextInspectMutateTheStoredContext(): void {
            $records = $this->captureLogs("ctx");

            logger("ctx")
                ->contextAdd(["x" => 1])
                ->contextInspect(fn($c) => array_merge($c, ["y" => 2]))
                ->info("inspected");

            $this->assertCount(1, $records->getRecords());
            $extra = $records->getRecords()[0]["extra"];
            $this->assertSame(1, $extra["x"]);
            $this->assertSame(2, $extra["y"]);
        }

        public function testContextInspectHandsTheStoredContextToTheCallback(): void {
            $seen = null;

            logger("ctx")->contextAdd(["x" => 1])->contextInspect(function($context) use (&$seen) {
                $seen = $context;
                return $context;
            });

            $this->assertSame(["x" => 1], $seen);
        }

        public function testShouldMergeContextFromAnotherLoggerWithSourceWinsPrecedence(): void {
            $records = $this->captureLogs("target");

            logger("source")->contextAdd(["x" => 1, "shared" => "from-source"]);
            logger("target")->contextAdd(["y" => 2, "shared" => "from-target"])
                ->contextMergeFrom("source")
                ->info("merged");

            $this->assertCount(1, $records->getRecords());
            $extra = $records->getRecords()[0]["extra"];
            // merged-from ("source") takes precedence on shared keys
            $this->assertSame("from-source", $extra["shared"]);
            // target keeps its own non-conflicting keys
            $this->assertSame(2, $extra["y"]);
            // source keys are added into target
            $this->assertSame(1, $extra["x"]);
        }

        public function testMergingContextLeavesTheSourceLoggerUntouched(): void {
            logger("source")->contextAdd(["x" => 1]);

            logger("target")->contextAdd(["y" => 2])->contextMergeFrom("source");

            $this->assertSame(["x" => 1], logger("source")->context);
            $this->assertSame(["y" => 2, "x" => 1], logger("target")->context);
        }

        public function testContextMergeFromTakesTheAppLoggerByDefault(): void {
            logger()->contextAdd(["from" => "app"]);
            logger("other")->contextAdd(["from" => "other"]);

            logger("target")->contextMergeFrom();

            $this->assertSame(["from" => "app"], logger("target")->context);
        }

        public function testShouldDropPriorContextAfterContextClear(): void {
            $records = $this->captureLogs("cleared");

            logger("cleared")->contextAdd(["x" => 1])->contextClear()->info("after clear");

            $this->assertCount(1, $records->getRecords());
            $this->assertArrayNotHasKey("x", $records->getRecords()[0]["extra"]);
            $this->assertSame([], logger("cleared")->context);
        }

        public function testShouldThrowInvalidArgumentExceptionWhenContextMergeFromIsCalledWithAnEmptyString(): void {
            $this->assertThrows(InvalidArgumentException::class, self::ERROR_EMPTY_NAME, function() {
                logger("x")->contextMergeFrom("");
            });
        }

        // "0" is a name like any other, PHP's empty() takes it for no name
        public function testContextMergeFromAcceptsALoggerNamedZero(): void {
            logger("0")->contextAdd(["from" => "zero"]);

            logger("target")->contextMergeFrom("0");

            $this->assertSame(["from" => "zero"], logger("target")->context);
        }

        public function testShouldThrowWhenContextMergeFromReferencesALoggerThatHasNotBeenCreated(): void {
            $this->assertThrows(\Exception::class, self::ERROR_UNKNOWN_LOGGER, function() {
                logger("x")->contextMergeFrom("does-not-exist");
            });
        }

        // Text is truthy for PHP, so "false" from an environment variable used to leave the logging on
        /** @dataProvider switchedOff */
        public function testLogsNothingWhenLoggingIsSwitchedOff(string $value): void {
            $this->setSettings(["logger_enabled" => $value]);

            $handlers = logger("switched-off")->getHandlers();

            $this->assertCount(1, $handlers);
            $this->assertInstanceOf(\Monolog\Handler\NullHandler::class, $handlers[0]);
        }

        public static function switchedOff(): array {
            return [
                "false in the config file" => [""],
                "zero" => ["0"],
                "false as text" => ["false"],
                "off as text" => ["off"],
            ];
        }

        // The logger the application gets from the factory, with its handler swapped for
        // one that keeps the records. The processors the factory attached stay in place.
        private function captureLogs(string $name): TestHandler {
            $handler = new TestHandler();
            logger($name)->setHandlers([$handler]);
            return $handler;
        }
    }

?>
