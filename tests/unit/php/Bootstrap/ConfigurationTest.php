<?php

    namespace Tests\Bootstrap;

    use InvalidArgumentException;
    use Tests\TestCase;

    class ConfigurationTest extends TestCase {

        private const VARIABLE = "CONFIG_PAGENAME";

        protected function tearDown(): void {
            putenv(self::VARIABLE);
            parent::tearDown();
        }

        // allow_env_config

        /** @dataProvider switchedOn */
        public function testAnEnvironmentVariableOverridesASettingWhenAllowed(string $line): void {
            putenv(self::VARIABLE . "=from the environment");

            $this->assertSame("from the environment", $this->load($line)->pageName);
        }

        public static function switchedOn(): array {
            return [
                "true" => ["allow_env_config = true"],
                "on" => ["allow_env_config = on"],
                "1" => ["allow_env_config = 1"],
                "a quoted true" => ['allow_env_config = "true"'],
            ];
        }

        // The check used to be plain truthiness, and the text "false" is truthy
        /** @dataProvider switchedOff */
        public function testAnEnvironmentVariableIsIgnoredWhenNotAllowed(string $line): void {
            putenv(self::VARIABLE . "=from the environment");

            $this->assertSame("from the file", $this->load($line)->pageName);
        }

        public static function switchedOff(): array {
            return [
                "no line at all" => [""],
                "false" => ["allow_env_config = false"],
                "off" => ["allow_env_config = off"],
                "0" => ["allow_env_config = 0"],
                "a quoted false" => ['allow_env_config = "false"'],
                "a quoted off" => ['allow_env_config = "off"'],
            ];
        }

        public function testASettingWithoutAnEnvironmentVariableKeepsItsValue(): void {
            $this->assertSame("from the file", $this->load("allow_env_config = true")->pageName);
        }

        public function testRejectsAnAllowEnvConfigThatIsNoSwitch(): void {
            $this->assertThrows(
                InvalidArgumentException::class,
                "Config key 'allow_env_config' must be a switch (true or false), got: 'ture'",
                fn() => $this->load("allow_env_config = ture"),
            );
        }

        // Loads a settings file that holds the given line next to the settings every application has
        private function load(string $line): object {
            $directory = $this->temporaryDirectory();
            mkdir($directory . "z_config");
            file_put_contents($directory . "z_config/z_settings.ini", implode("\n", [
                "host = http://localhost",
                "rootDirectory =",
                "pageName = from the file",
                $line,
            ]));

            $framework = $this->bootFramework();
            $this->inDirectory($directory, fn() => $framework->loadConfiguration($directory, []));

            return $framework;
        }
    }

?>
