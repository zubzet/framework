<?php

    namespace Tests\ErrorHandling\DebugBar\Collectors;

    use Monolog\Logger;
    use Tests\TestCase;
    use ZubZet\Framework\ErrorHandling\DebugBar\Collectors\MonologCollector;

    class MonologCollectorTest extends TestCase {

        private const RENDER_CONTEXT = [
            "location" => "Core/render",
            "view" => "core/render",
            "viewName" => "core.render",
            "layout" => "layout/default_layout.php",
            "layoutName" => "layout.default_layout",
        ];

        private MonologCollector $collector;
        private Logger $logger;

        protected function setUp(): void {
            // Rendering the record goes through the escaping helper
            $this->bootFramework();

            $this->logger = new Logger("zubzet");
            $this->collector = new MonologCollector($this->logger);
        }

        public function testExposesContextFieldsUnderTheContextPrefix(): void {
            $this->logger->info("RENDER", self::RENDER_CONTEXT);

            $record = $this->collector->collect()["records"][0];

            $this->assertSame(
                "zubzet INFO RENDER context.location=Core/render context.view=core/render"
                    . " context.viewName=core.render context.layout=layout/default_layout.php"
                    . " context.layoutName=layout.default_layout",
                $record["message"],
            );
            $this->assertSame("info", $record["label"]);
            $this->assertTrue($record["is_string"]);
        }

        public function testListsEveryContextFieldAsAHighlightedRowOfTheHtml(): void {
            $this->logger->info("RENDER", self::RENDER_CONTEXT);

            $html = $this->collector->collect()["records"][0]["message_html"];

            foreach(self::RENDER_CONTEXT as $key => $value) {
                $this->assertStringContainsString("<td class=\"pr-3 text-primary\">context.$key</td>", $html);
                $this->assertStringContainsString("<td><pre>$value</pre></td>", $html);
            }
        }

        public function testExposesProcessorExtrasUnderTheExtraPrefix(): void {
            $this->logger->pushProcessor(function(array $record) {
                $record["extra"]["file"] = "a.php";
                return $record;
            });

            $this->logger->info("RENDER", ["view" => "core/render"]);

            $record = $this->collector->collect()["records"][0];
            $this->assertSame("zubzet INFO RENDER context.view=core/render extra.file=a.php", $record["message"]);
            $this->assertStringContainsString("<td class=\"pr-3 text-info\">extra.file</td>", $record["message_html"]);
        }

        public function testKeepsAContextAndAnExtraFieldOfTheSameNameApart(): void {
            $this->logger->pushProcessor(function(array $record) {
                $record["extra"]["view"] = "from-extra";
                return $record;
            });

            $this->logger->info("RENDER", ["view" => "from-context"]);

            $this->assertSame(
                "zubzet INFO RENDER context.view=from-context extra.view=from-extra",
                $this->collector->collect()["records"][0]["message"],
            );
        }

        public function testPutsTheTraceIdInFrontOfTheMessage(): void {
            $this->logger->pushProcessor(function(array $record) {
                $record["extra"]["traceId"] = "abc123";
                return $record;
            });

            $this->logger->warning("slow");

            $record = $this->collector->collect()["records"][0];
            $this->assertSame("abc123 zubzet WARNING slow extra.traceId=abc123", $record["message"]);
            $this->assertStringContainsString("abc123 [zubzet WARNING]: slow", $record["message_html"]);
        }

        public function testShowsOnlyTheHeadlineForARecordWithoutFields(): void {
            $this->logger->info("RENDER");

            $record = $this->collector->collect()["records"][0];
            $this->assertSame("zubzet INFO RENDER", $record["message"]);
            $this->assertStringContainsString("[zubzet INFO]: RENDER", $record["message_html"]);
            $this->assertStringNotContainsString("<details>", $record["message_html"]);
        }

        /** @dataProvider valueTypes */
        public function testFormatsFieldValuesByTheirType(mixed $value, string $expected): void {
            $this->logger->info("RENDER", ["value" => $value]);

            $this->assertSame(
                "zubzet INFO RENDER context.value=$expected",
                $this->collector->collect()["records"][0]["message"],
            );
        }

        public static function valueTypes(): array {
            return [
                "string" => ["text", "text"],
                "integer" => [5, "5"],
                "float" => [1.5, "1.5"],
                "true" => [true, "true"],
                "false" => [false, "false"],
                "null" => [null, "NULL"],
                "array" => [[1, 2], "[\n    1,\n    2\n]"],
            ];
        }

        public function testEscapesFieldValuesInTheHtml(): void {
            $this->logger->info("a & b", ["value" => "x \"y\" & z"]);

            $html = $this->collector->collect()["records"][0]["message_html"];
            $this->assertStringContainsString("[zubzet INFO]: a &amp; b", $html);
            $this->assertStringContainsString("<pre>x &quot;y&quot; &amp; z</pre>", $html);
        }
    }

?>
