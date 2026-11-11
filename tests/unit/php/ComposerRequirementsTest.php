<?php

    namespace Tests;

    use PHPUnit\Framework\TestCase as BaseTestCase;

    class ComposerRequirementsTest extends BaseTestCase {

        private const ROOT = __DIR__ . "/../../../";

        // Translation::locale() calls \Locale::lookup(), which only exists with the extension
        public function testRequiresTheIntlExtensionTheTranslationNeeds(): void {
            $this->assertStringContainsString(
                "\\Locale::",
                file_get_contents(self::ROOT . "src/Translation/Translation.php"),
                "The translation no longer uses the extension, the requirement can go.",
            );

            $this->assertArrayHasKey("ext-intl", $this->read("composer.json")["require"]);
        }

        public function testTheLockFileKnowsTheSameExtensions(): void {
            $required = array_filter(
                $this->read("composer.json")["require"],
                fn(string $package) => str_starts_with($package, "ext-"),
                ARRAY_FILTER_USE_KEY,
            );

            $locked = array_filter(
                $this->read("composer.lock")["platform"],
                fn(string $package) => str_starts_with($package, "ext-"),
                ARRAY_FILTER_USE_KEY,
            );

            $this->assertSame($required, $locked);
        }

        /** @return array<string, mixed> */
        private function read(string $file): array {
            return json_decode(file_get_contents(self::ROOT . $file), true, 512, JSON_THROW_ON_ERROR);
        }
    }

?>
