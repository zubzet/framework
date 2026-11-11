<?php

    namespace Tests\Support\Commands;

    use Composer\InstalledVersions;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use Symfony\Component\Console\Tester\CommandTester;
    use Tests\TestCase;
    use ZubZet\Framework\Support\Commands\Install;

    class InstallTest extends TestCase {

        private const SETTINGS = "z_config/z_settings.ini";
        private const KEY_LINE = '/^encryption_key = ([0-9a-f]{64})$/m';

        private int $exitCode;

        public function testFailsForAMissingFolder(): void {
            $missing = $this->temporaryDirectory() . "missing";

            $output = $this->install($missing);

            $this->assertSame(1, $this->exitCode);
            $this->assertSame("Folder does not exist: {$missing}\n", $output);
            $this->assertDirectoryDoesNotExist($missing);
        }

        public function testFailsForAFileInsteadOfAFolder(): void {
            $file = $this->temporaryDirectory() . "file";
            touch($file);

            $output = $this->install($file);

            $this->assertSame(1, $this->exitCode);
            $this->assertSame("Folder does not exist: {$file}\n", $output);
        }

        public function testFailsForAFolderThatIsNotWritable(): void {
            // Root may write into every folder, so the check can not fail there
            if(function_exists("posix_geteuid") && 0 === posix_geteuid()) {
                $this->markTestSkipped("Running as root, every folder is writable.");
            }

            $project = $this->temporaryDirectory();
            chmod($project, 0555);

            try {
                $output = $this->install($project);
            } finally {
                chmod($project, 0755);
            }

            $this->assertSame(1, $this->exitCode);
            $this->assertSame("Folder is not writable: " . realpath($project) . "\n", $output);
            $this->assertSame([], $this->entriesOf($project));
        }

        public function testBuildsTheProjectFromTheTemplate(): void {
            $project = $this->temporaryDirectory();

            $output = $this->install($project);

            $this->assertSame(0, $this->exitCode, $output);
            $this->assertStringContainsString("created: index.php\n", $output);
            $this->assertStringContainsString("created: z_config/z_settings.ini\n", $output);
            $this->assertStringContainsString("created: app/Controllers/ExampleController.php\n", $output);

            // copy() drops the executable bit, the command sets it again
            $this->assertTrue(is_executable($project . "zubzet"));
            $this->assertTrue(is_executable($project . "project"));
        }

        public function testCopiesEveryFileOfTheTemplateAndReportsEachOne(): void {
            $project = $this->temporaryDirectory();

            $output = $this->install($project);

            $template = $this->template();
            $created = [];
            foreach($this->entriesOf($template) as $entry) {
                $created[] = "created: {$entry}";
                $this->assertFileExists($project . $entry);
                if(is_dir($template . "/" . $entry)) continue;

                // The settings file also gets the encryption key
                if(self::SETTINGS === $entry) continue;
                $this->assertFileEquals($template . "/" . $entry, $project . $entry, $entry);
            }

            $reported = array_filter(explode("\n", $output), fn($line) => str_starts_with($line, "created: "));
            $this->assertEqualsCanonicalizing($created, array_values($reported));
            $this->assertSame([], array_diff($this->entriesOf($project), $this->entriesOf($template)));
        }

        public function testNeverOverwritesFilesAlreadyInTheProject(): void {
            $project = $this->temporaryDirectory();
            file_put_contents($project . "index.php", "keep");

            $output = $this->install($project);

            $this->assertSame(0, $this->exitCode, $output);
            $this->assertStringContainsString("exists:  index.php\n", $output);
            $this->assertStringNotContainsString("created: index.php\n", $output);
            $this->assertStringContainsString("created: project\n", $output);
            $this->assertSame("keep", file_get_contents($project . "index.php"));
        }

        public function testKeepsAFolderThatExistsAndFillsItWithTheMissingFiles(): void {
            $project = $this->temporaryDirectory();
            mkdir($project . "app");
            file_put_contents($project . "app/mine.php", "mine");

            $output = $this->install($project);

            $this->assertSame(0, $this->exitCode, $output);
            $this->assertStringContainsString("exists:  app\n", $output);
            $this->assertStringContainsString("created: app/Controllers/ExampleController.php\n", $output);
            $this->assertSame("mine", file_get_contents($project . "app/mine.php"));
        }

        // The encryption key

        public function testGeneratesAnEncryptionKeyWhenTheTemplateHasNone(): void {
            $project = $this->temporaryDirectory();

            $output = $this->install($project);

            $this->assertStringContainsString("generated: encryption_key in z_config/z_settings.ini\n", $output);
            $content = file_get_contents($project . self::SETTINGS);
            $this->assertSame(1, preg_match_all(self::KEY_LINE, $content), $content);
            $this->assertSame(1, preg_match_all('/^encryption_key/m', $content));
        }

        public function testKeepsAnExistingEncryptionKey(): void {
            $project = $this->temporaryDirectory();
            mkdir($project . "z_config");
            $settings = "pageName = Mine\nencryption_key = my-own-key\n";
            file_put_contents($project . self::SETTINGS, $settings);

            $output = $this->install($project);

            $this->assertStringNotContainsString("generated:", $output);
            $this->assertSame($settings, file_get_contents($project . self::SETTINGS));
        }

        public function testAppendsAnEncryptionKeyWhenTheSettingsHaveNoKeyLine(): void {
            $project = $this->temporaryDirectory();
            mkdir($project . "z_config");
            file_put_contents($project . self::SETTINGS, "pageName = Mine\n\n");

            $output = $this->install($project);

            $this->assertStringContainsString("generated: encryption_key in z_config/z_settings.ini\n", $output);
            $content = file_get_contents($project . self::SETTINGS);
            $this->assertSame(1, preg_match("/^pageName = Mine\nencryption_key = [0-9a-f]{64}\n$/", $content), $content);
        }

        public function testKeepsTheGeneratedKeyWhenRunAgain(): void {
            $project = $this->temporaryDirectory();
            $this->install($project);
            $settings = file_get_contents($project . self::SETTINGS);

            $output = $this->install($project);

            $this->assertSame(0, $this->exitCode, $output);
            $this->assertStringNotContainsString("generated:", $output);
            $this->assertStringNotContainsString("created:", $output);
            $this->assertSame($settings, file_get_contents($project . self::SETTINGS));
        }

        public function testGeneratesADifferentKeyForEveryProject(): void {
            $first = $this->temporaryDirectory();
            $second = $this->temporaryDirectory();
            $this->install($first);
            $this->install($second);

            preg_match(self::KEY_LINE, file_get_contents($first . self::SETTINGS), $firstKey);
            preg_match(self::KEY_LINE, file_get_contents($second . self::SETTINGS), $secondKey);

            $this->assertNotSame($firstKey[1], $secondKey[1]);
        }

        private function install(string $path): string {
            $tester = new CommandTester(new Install());
            $this->exitCode = $tester->execute(["path" => $path]);

            return $tester->getDisplay();
        }

        // The template folder, found the way the command finds it
        private function template(): string {
            return realpath(InstalledVersions::getInstallPath("zubzet/framework") . "/stubs/project");
        }

        /** @return string[] Paths below the folder, relative to it */
        private function entriesOf(string $folder): array {
            $folder = rtrim($folder, "/");
            $entries = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
            );

            $paths = [];
            foreach($entries as $entry) {
                $paths[] = substr($entry->getPathname(), strlen($folder) + 1);
            }
            return $paths;
        }
    }

?>
