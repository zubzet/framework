<?php

    namespace Tests\Rendering;

    use Closure;
    use Tests\TestCase;
    use ZubZet\Framework\Message\Response;

    class CanRenderViewTest extends TestCase {

        private const HOST = "http://localhost:8080";

        protected function setUp(): void {
            parent::setUp();
            $this->bootFramework([
                "rootFolder" => "/",
                "host" => self::HOST,
                "pageName" => "QA Suite",
                "assetVersion" => "1.0.0",
            ]);
            $this->request();
            $this->response();
        }

        // $opt contract: data shape

        public function testInjectsTheFrameworkExpansionsIntoOpt(): void {
            $user = $this->currentUser();

            $expansion = $this->expand([]);

            $this->assertSame(["opt"], array_keys($expansion));
            $this->assertSame("/", $expansion["opt"]["root"]);
            $this->assertSame(self::HOST, $expansion["opt"]["host"]);
            $this->assertSame(self::HOST . "/", $expansion["opt"]["absRoot"]);
            $this->assertSame($user, $expansion["opt"]["user"]);
            $this->assertSame(request(), $expansion["opt"]["request"]);
            $this->assertSame(response(), $expansion["opt"]["response"]);
            $this->assertInstanceOf(Closure::class, $expansion["opt"]["generateResourceLink"]);
        }

        public function testKeepsTheCallerDataInOpt(): void {
            $opt = $this->expand(["a" => "alpha", "list" => [1, 2]])["opt"];

            $this->assertSame("alpha", $opt["a"]);
            $this->assertSame([1, 2], $opt["list"]);
        }

        public function testLeavesTheUserOutWhenThereIsNone(): void {
            $this->assertNull($this->expand([])["opt"]["user"]);
        }

        public function testTheFrameworkExpansionsOverwriteCallerKeysOfTheSameName(): void {
            $opt = $this->expand(["root" => "caller", "host" => "caller", "absRoot" => "caller"])["opt"];

            $this->assertSame("/", $opt["root"]);
            $this->assertSame(self::HOST, $opt["host"]);
            $this->assertSame(self::HOST . "/", $opt["absRoot"]);
        }

        public function testAbsRootAppendsTheRootFolderToTheHost(): void {
            $this->setSettings(["rootFolder" => "/sub/", "host" => "https://example.org"]);

            $opt = $this->expand([])["opt"];

            $this->assertSame("/sub/", $opt["root"]);
            $this->assertSame("https://example.org", $opt["host"]);
            $this->assertSame("https://example.org/sub/", $opt["absRoot"]);
        }

        public function testEchoEscapesHtmlAndKeepsLineBreaks(): void {
            $echo = $this->expand([])["opt"]["echo"];

            ob_start();
            $echo("<b>one</b>\ntwo & \"three\"");
            $output = ob_get_clean();

            $this->assertSame("&lt;b&gt;one&lt;/b&gt;<br />\ntwo &amp; &quot;three&quot;", $output);
        }

        // Title

        public function testPreservesACallerSuppliedTitle(): void {
            $this->assertSame("Custom Title Marker", $this->expand(["title" => "Custom Title Marker"])["opt"]["title"]);
        }

        public function testFallsBackToConfigPageNameWhenTheCallerPassesNone(): void {
            $this->assertSame("QA Suite", $this->expand(["a" => "alpha"])["opt"]["title"]);
        }

        public function testFallsBackToConfigPageNameWhenTheCallerTitleIsNull(): void {
            $this->assertSame("QA Suite", $this->expand(["title" => null])["opt"]["title"]);
        }

        public function testFallsBackToTheFrameworkNameWhenNoPageNameIsConfigured(): void {
            $this->removeSetting("pageName");

            $this->assertSame("ZubZet", $this->expand([])["opt"]["title"]);
        }

        // generateResourceLink

        public function testAppendsTheFixedAssetVersionAndHonorsTheRootToggle(): void {
            $this->assertSame("/demo/asset.js?v=1.0.0", $this->resourceLink("demo/asset.js"));
            $this->assertSame("/demo/asset.js?v=1.0.0", $this->resourceLink("demo/asset.js", true));
            $this->assertSame("demo/asset.js?v=1.0.0", $this->resourceLink("demo/asset.js", false));
        }

        public function testPrefixesTheConfiguredRootFolder(): void {
            $this->setSettings(["rootFolder" => "/sub/"]);

            $this->assertSame("/sub/demo/asset.js?v=1.0.0", $this->resourceLink("demo/asset.js"));
            $this->assertSame("demo/asset.js?v=1.0.0", $this->resourceLink("demo/asset.js", false));
        }

        public function testUsesATimeBasedVersionWhenAssetVersionIsDev(): void {
            $this->setSettings(["assetVersion" => "dev"]);

            $before = time();
            $link = $this->resourceLink("demo/asset.js");
            $after = time();

            $this->assertMatchesRegularExpression('/^\/demo\/asset\.js\?v=\d{10}$/', $link);
            $this->assertGreaterThanOrEqual($before, (int) substr($link, -10));
            $this->assertLessThanOrEqual($after, (int) substr($link, -10));
        }

        public function testTheTimeBasedVersionAlsoHonorsTheRootToggle(): void {
            $this->setSettings(["assetVersion" => "dev"]);

            $this->assertMatchesRegularExpression('/^demo\/asset\.js\?v=\d{10}$/', $this->resourceLink("demo/asset.js", false));
        }

        private function expand(array $opt): array {
            return $this->callHidden(Response::class, "legacyOptExpansion", $opt);
        }

        // The closure echoes the link
        private function resourceLink(string $url, ?bool $root = null): string {
            $generate = $this->expand([])["opt"]["generateResourceLink"];

            // Without a toggle the closure's own default applies
            $arguments = null === $root ? [$url] : [$url, $root];

            ob_start();
            $generate(...$arguments);
            return ob_get_clean();
        }
    }

?>
