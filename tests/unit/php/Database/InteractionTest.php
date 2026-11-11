<?php

    namespace Tests\Database;

    use Tests\TestCase;
    use ZubZet\Framework\Database\Connection;

    class InteractionTest extends TestCase {

        private const FIRST_A = ["group_id" => 1, "label" => "A", "val" => 10];
        private const FIRST_B = ["group_id" => 1, "label" => "B", "val" => 20];
        private const SECOND_C = ["group_id" => 2, "label" => "C", "val" => 30];

        protected function setUp(): void {
            $this->bootFramework();
        }

        // mergeAsGroup($groupBy)

        public function testGroupsRowsByTheColumnFullAssocPerEntry(): void {
            $connection = $this->connectionReturning([self::FIRST_A, self::FIRST_B, self::SECOND_C]);

            $this->assertSame([
                1 => [self::FIRST_A, self::FIRST_B],
                2 => [self::SECOND_C],
            ], $connection->mergeAsGroup("group_id"));
        }

        public function testSingleGroupResultStillConstructsTheNewKeyBranchCorrectly(): void {
            $connection = $this->connectionReturning([self::SECOND_C]);

            $this->assertSame([2 => [self::SECOND_C]], $connection->mergeAsGroup("group_id"));
        }

        public function testEmptyResultReturnsAnEmptyObject(): void {
            $connection = $this->connectionReturning([]);

            $this->assertSame([], $connection->mergeAsGroup("group_id"));
        }

        public function testKeepsTheOrderOfTheGroupsAndOfTheRowsInside(): void {
            $third = ["group_id" => 1, "label" => "D", "val" => 5];
            $connection = $this->connectionReturning([self::FIRST_B, self::SECOND_C, self::FIRST_A, $third]);

            $groups = $connection->mergeAsGroup("group_id");

            $this->assertSame([1, 2], array_keys($groups));
            $this->assertSame([self::FIRST_B, self::FIRST_A, $third], $groups[1]);
        }

        // mergeAsGroup($groupBy, $subElement)

        public function testCollectsTheSubElementValuesKeyedByGroup(): void {
            $connection = $this->connectionReturning([self::FIRST_A, self::FIRST_B, self::SECOND_C]);

            $this->assertSame([1 => [10, 20], 2 => [30]], $connection->mergeAsGroup("group_id", "val"));
        }

        public function testAppendsANullSubElementValue(): void {
            $connection = $this->connectionReturning([["group_id" => 1, "val" => null], ["group_id" => 1, "val" => 7]]);

            $this->assertSame([1 => [null, 7]], $connection->mergeAsGroup("group_id", "val"));
        }

        // A fake of the mysqli result: mergeAsGroup() only asks it for the next row
        private function connectionReturning(array $rows): Connection {
            $connection = new Connection();
            $connection->result = new class($rows) {
                public function __construct(private array $rows) {}

                public function fetch_assoc(): ?array {
                    return array_shift($this->rows);
                }
            };
            return $connection;
        }
    }

?>
