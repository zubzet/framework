<?php

    namespace Tests\ErrorHandling\DebugBar\Collectors;

    use Tests\TestCase;
    use ZubZet\Framework\ErrorHandling\DebugBar\Collectors\TemplateCollector;

    class TemplateCollectorTest extends TestCase {

        public function testFormatsTemplateParamsViaDataFormatter(): void {
            $collector = new TemplateCollector();

            $collector->addTemplate("core/render", ["data" => "Data"], "blade", "layout/default_layout.php");

            $template = $collector->collect()["templates"][0];
            $this->assertSame(["data" => '"Data"'], $template["params"]);
            $this->assertSame(1, $template["param_count"]);
        }

        public function testKeepsTheNameOfEveryParamAndCountsThem(): void {
            $collector = new TemplateCollector();

            $collector->addTemplate("core/render", ["title" => "Hello", "user" => "Ada", "page" => "home"], "blade", "layout/default_layout.php");

            $template = $collector->collect()["templates"][0];
            $this->assertSame(["title", "user", "page"], array_keys($template["params"]));
            $this->assertSame(3, $template["param_count"]);
        }

        public function testHasNoParamsForATemplateWithoutData(): void {
            $collector = new TemplateCollector();

            $collector->addTemplate("core/empty", [], "blade", "layout/min_layout.php");

            $template = $collector->collect()["templates"][0];
            $this->assertSame([], $template["params"]);
            $this->assertSame(0, $template["param_count"]);
        }
    }

?>
