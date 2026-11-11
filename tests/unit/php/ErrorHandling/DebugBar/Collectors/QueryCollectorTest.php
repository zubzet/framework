<?php

    namespace Tests\ErrorHandling\DebugBar\Collectors;

    use Tests\TestCase;
    use ZubZet\Framework\Core\Model;
    use ZubZet\Framework\Database\IsInternalModel;
    use ZubZet\Framework\ErrorHandling\DebugBar\Collectors\QueryCollector;

    class QueryCollectorTest extends TestCase {

        private const INSERT = "INSERT INTO `model_test_insert` (`value`)\n                    VALUES (?)";
        private const SELECT = "SELECT *\n                    FROM `model_test_select`";
        private const USER_QUERY = "SELECT * FROM `z_user` WHERE `id` = ?";

        private QueryCollector $collector;

        protected function setUp(): void {
            $this->bootFramework();
            $this->collector = new QueryCollector();
        }

        public function testShowsSqlWithPlaceholdersInterpolatedAsSingleQuotedValues(): void {
            $this->collector->addQuery(self::INSERT, values: ["TestData"]);

            $this->assertSame(
                ["INSERT INTO `model_test_insert` (`value`)\nVALUES ('TestData')"],
                $this->collectedSql(),
            );
        }

        public function testInterpolatesEveryPlaceholderInOrderAndTurnsNullIntoNull(): void {
            $this->collector->addQuery("UPDATE t SET a = ?, b = ?, c = ? WHERE id = ?", values: ["x", null, 5, 7]);

            $this->assertSame(["UPDATE t SET a = 'x', b = NULL, c = '5' WHERE id = '7'"], $this->collectedSql());
        }

        public function testEscapesQuotesInInterpolatedValues(): void {
            $this->collector->addQuery("SELECT ?", values: ["O'Reilly"]);

            $this->assertSame(["SELECT 'O\\'Reilly'"], $this->collectedSql());
        }

        public function testKeepsPlaceholdersWithoutAValueAndIgnoresSurplusValues(): void {
            $this->collector->addQuery("SELECT ?, ?", values: ["only"]);
            $this->collector->addQuery("SELECT ?", values: ["used", "surplus"]);
            $this->collector->addQuery("SELECT ?");

            $this->assertSame(["SELECT 'only', ?", "SELECT 'used'", "SELECT ?"], $this->collectedSql());
        }

        /** @dataProvider literalsAndComments */
        public function testLeavesQuestionMarksInsideLiteralsAndCommentsAlone(string $sql, string $expected): void {
            $this->collector->addQuery($sql, values: ["v"]);

            $this->assertSame([$expected], $this->collectedSql());
        }

        public static function literalsAndComments(): array {
            return [
                "single-quoted literal" => ["SELECT '?', ?", "SELECT '?', 'v'"],
                "double-quoted literal" => ['SELECT "?", ?', 'SELECT "?", \'v\''],
                "line comment" => ["SELECT ? -- why?\n, 1", "SELECT 'v' -- why?\n, 1"],
                "block comment" => ["SELECT /* ? */ ?", "SELECT /* ? */ 'v'"],
            ];
        }

        public function testStripsLeadingIndentationFromMultiLineSql(): void {
            $this->collector->addQuery(self::SELECT);

            $this->assertSame(["SELECT *\nFROM `model_test_select`"], $this->collectedSql());
        }

        public function testStripsTabsAndTrimsTheStatement(): void {
            $this->collector->addQuery("\n\t  SELECT 1\n\t\tFROM dual\n  ");

            $this->assertSame(["SELECT 1\nFROM dual"], $this->collectedSql());
        }

        public function testHidesInternalModelQueriesWhenDebugbarHideInternalQueriesIsTrue(): void {
            // The config file turns "true" into "1"
            $this->setSettings(["debugbar_hide_internal_queries" => "1"]);

            $this->collector->addQuery(self::USER_QUERY, values: [1], model: $this->internalModel());

            $this->assertSame([], $this->collectedSql());
            $this->assertSame(0, $this->collector->collect()["nb_statements"]);
        }

        public function testStillShowsQueriesOfOtherModelsWhenInternalOnesAreHidden(): void {
            $this->setSettings(["debugbar_hide_internal_queries" => "1"]);

            $this->collector->addQuery("SELECT 1", model: $this->ordinaryModel());
            $this->collector->addQuery("SELECT 2");

            $this->assertSame(["SELECT 1", "SELECT 2"], $this->collectedSql());
        }

        public function testHidesInternalModelQueriesWhenTheSettingIsMissing(): void {
            // Nothing is configured in setUp()
            $this->collector->addQuery(self::USER_QUERY, values: [1], model: $this->internalModel());

            $this->assertSame([], $this->collectedSql());
        }

        /** @dataProvider falseSettings */
        public function testShowsInternalModelQueriesWhenDebugbarHideInternalQueriesIsFalse(string $setting): void {
            $this->setSettings(["debugbar_hide_internal_queries" => $setting]);

            $this->collector->addQuery(self::USER_QUERY, values: [1], model: $this->internalModel());

            $this->assertSame(["SELECT * FROM `z_user` WHERE `id` = '1'"], $this->collectedSql());
        }

        public static function falseSettings(): array {
            return [
                // The config file turns "false" into an empty string
                "false in the config file" => [""],
                "zero" => ["0"],
                // Text is truthy for PHP, so "false" used to hide the queries
                "false as text, as an environment variable sets it" => ["false"],
                "off as text" => ["off"],
            ];
        }

        /** @return string[] */
        private function collectedSql(): array {
            return array_column($this->collector->collect()["statements"], "sql");
        }

        private function internalModel(): Model {
            return new class extends Model {
                use IsInternalModel;

                public function __construct() {}
            };
        }

        private function ordinaryModel(): Model {
            return new class extends Model {
                public function __construct() {}
            };
        }
    }

?>
