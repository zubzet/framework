<?php

    namespace Tests\Support\Commands;

    use Composer\InstalledVersions;
    use Symfony\Component\Console\Tester\CommandTester;
    use Tests\TestCase;
    use ZubZet\Framework\Support\Commands\Startup;
    use ZubZet\Framework\Support\RuntimeCache\InternalRuntimeCache;

    class StartupTest extends TestCase {

        private const SEPARATOR = "  ─────────────────────────────────────────────";

        protected function setUp(): void {
            $this->bootFramework([
                "pageName" => "Unit Test Page",
                "execution_type" => "test",
                "assetVersion" => "7",
                "host" => "http://localhost:8080",
            ]);
            $this->setModules([]);
        }

        public function testPrintsTheStartupInfo(): void {
            $display = $this->runStartup($this->temporaryDirectory());

            $this->assertSame(implode("\n", [
                "  ",
                "  ▲ ZubZet  " . InstalledVersions::getPrettyVersion("zubzet/framework") . "  Unit Test Page",
                "  ",
                "  ┃  Open:  http://localhost:8080",
                "  ",
                self::SEPARATOR,
                "  Environment\ttest",
                "  PHP Runtime\tv" . PHP_VERSION,
                "  Assets\tv7",
                self::SEPARATOR,
                "  ",
                "  run 'npm run stop' to stop the server",
                "  ",
                "",
            ]), $display);
        }

        public function testPrintsOneRowPerInstalledModuleInResolutionOrder(): void {
            $this->setModules(["zubzet/example-theme", "zubzet/example-guestbook"]);

            $display = $this->runStartup($this->temporaryDirectory());

            $this->assertStringContainsString(
                "  Assets\tv7\n  Module\tzubzet/example-theme\n  Module\tzubzet/example-guestbook\n" . self::SEPARATOR,
                $display,
            );
        }

        public function testNamesTheMissingSettings(): void {
            $this->removeSetting("pageName");
            $this->removeSetting("execution_type");
            $this->removeSetting("assetVersion");
            $this->removeSetting("host");

            $display = $this->runStartup($this->temporaryDirectory());

            $this->assertStringContainsString("  Unknown. Set pageName in settings!\n", $display);
            $this->assertStringContainsString("Open:  Unknown / Check docker-compose-base.yml\n", $display);
            $this->assertStringContainsString("Environment\tUnknown. Set execution_type in settings!\n", $display);
            $this->assertStringContainsString("Assets\tvUnknown. Set assetVersion in settings!\n", $display);
        }

        // In a project that has a project script

        public function testPointsToProjectToStopTheServer(): void {
            $directory = $this->temporaryDirectory();
            touch($directory . "project");

            $display = $this->runStartup($directory);

            $this->assertStringContainsString("  run './project stop' to stop the server\n", $display);
            $this->assertStringNotContainsString("npm run stop", $display);
        }

        public function testPointsToNpmWhenProjectIsADirectory(): void {
            $directory = $this->temporaryDirectory();
            mkdir($directory . "project");

            $display = $this->runStartup($directory);

            $this->assertStringContainsString("run 'npm run stop' to stop the server", $display);
            $this->assertStringNotContainsString("./project stop", $display);
        }

        public function testExitsWithCode0(): void {
            $tester = new CommandTester(new Startup());

            $exitCode = $this->inDirectory($this->temporaryDirectory(), fn() => $tester->execute([]));

            $this->assertSame(0, $exitCode);
        }

        /** @param string[] $packages */
        private function setModules(array $packages): void {
            InternalRuntimeCache::set("registry", "module_packages", $packages);
        }

        // The output without the color codes the command switches on
        private function runStartup(string $workingDirectory): string {
            $tester = new CommandTester(new Startup());
            $this->inDirectory($workingDirectory, fn() => $tester->execute([]));

            return preg_replace("/\e\[[0-9;]*m/", "", $tester->getDisplay());
        }
    }

?>
