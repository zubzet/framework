<?php

    namespace Tests\Core;

    use InvalidArgumentException;
    use Tests\TestCase;
    use ZubZet\Framework\Support\RuntimeCache\InternalRuntimeCache;

    class CanRetrieveBooterSettingsTest extends TestCase {

        private const APP_SETTINGS = "host = http://localhost\nrootDirectory =\npageName = Unit Test Page\n";
        private const MODULE_SETTINGS = "guestbook_title = \"Signatures Book\"\n";

        // Module defaults

        public function testFallsBackToTheCodeDefaultBeforeAnyMerge(): void {
            // The module ships a default, but only module:setup copies it into the settings
            $module = $this->temporaryDirectory();
            mkdir($module . "z_config");
            file_put_contents($module . "z_config/z_settings.ini", self::MODULE_SETTINGS);
            InternalRuntimeCache::set("registry", "module_roots", ["zubzet/example-guestbook" => rtrim($module, "/")]);

            $this->bootFromSettingsFile(self::APP_SETTINGS);

            $this->assertSame("fallback-title", config("guestbook_title", default: "fallback-title"));
        }

        public function testReturnsTheConfiguredValueInsteadOfTheDefault(): void {
            $this->bootFromSettingsFile(self::APP_SETTINGS . "guestbook_title = \"Signatures Book\"\n");

            $this->assertSame("Signatures Book", config("guestbook_title", default: "fallback-title"));
        }

        public function testThrowsForAMissingSettingWhenDefaultsAreOff(): void {
            $this->bootFromSettingsFile(self::APP_SETTINGS);

            $this->assertThrows(
                InvalidArgumentException::class,
                "The setting 'guestbook_title' does not exist!",
                fn() => config("guestbook_title", false),
            );
        }

        private function bootFromSettingsFile(string $content): void {
            $directory = $this->temporaryDirectory();
            mkdir($directory . "z_config");
            file_put_contents($directory . "z_config/z_settings.ini", $content);

            $this->bootFramework();
            $frameworkRoot = dirname(__DIR__, 4) . "/src/";
            $this->inDirectory($directory, fn() => zubzet()->loadConfiguration($frameworkRoot, []));
        }
    }

?>
