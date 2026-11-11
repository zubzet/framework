<?php

    namespace Tests\Database;

    use InvalidArgumentException;
    use Tests\TestCase;
    use ZubZet\Framework\Database\Connection;

    class ConnectionTest extends TestCase {

        protected function setUp(): void {
            $this->bootFramework();
        }

        // heartbeat()

        // Connections open lazily, so there can be nothing to ping yet
        public function testDoesNotFatalBeforeTheLazyConnectionIsOpenedReturnsNotAlive(): void {
            $connection = new Connection();

            $this->assertFalse($connection->heartbeat(false));
        }

        public function testAForcedHeartbeatBeforeTheLazyConnectionIsOpenedLeavesNoTimestamp(): void {
            $connection = new Connection();
            $connection->heartbeat(false);

            $this->assertFalse(isset($connection->lastHeartbeat));
        }

        public function testAnExpiredHeartbeatBeforeTheLazyConnectionIsOpenedIsNotAlive(): void {
            $connection = new Connection();
            $connection->lastHeartbeat = 1;

            $this->assertFalse($connection->heartbeat(true));
        }

        // Constructor

        public function testThrowsInvalidArgumentExceptionOnNonNumericDbConnectionTimeout(): void {
            $this->setSettings(["db_connection_timeout" => "not-a-number"]);

            $this->assertThrows(
                InvalidArgumentException::class,
                "Config key 'db_connection_timeout' must be numeric, got: 'not-a-number'",
                fn() => new Connection()
            );
        }

        public function testThrowsInvalidArgumentExceptionOnNonNumericDbMaxRetries(): void {
            $this->setSettings(["db_max_retries" => "abc"]);

            $this->assertThrows(
                InvalidArgumentException::class,
                "Config key 'db_max_retries' must be numeric, got: 'abc'",
                fn() => new Connection()
            );
        }

        public function testTheTimeoutAndTheRetriesHaveDefaults(): void {
            $connection = new Connection();

            $this->assertSame(900, $connection->connectTimeout);
            $this->assertSame(3, $connection->maxRetries);
        }

        public function testReadsTheNumbersOfTheSettings(): void {
            $this->setSettings(["db_connection_timeout" => "1e3", "db_max_retries" => "2"]);

            $connection = new Connection();

            $this->assertSame(1000, $connection->connectTimeout);
            $this->assertSame(2, $connection->maxRetries);
        }

        public function testANegativeRetryBudgetDisablesTheRetries(): void {
            $this->setSettings(["db_max_retries" => "-5"]);

            $this->assertSame(0, (new Connection())->maxRetries);
        }
    }

?>
