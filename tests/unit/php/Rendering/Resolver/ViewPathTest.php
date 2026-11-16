<?php

    namespace Tests\Rendering\Resolver;

    use Tests\TestCase;
    use ZubZet\Framework\Message\Response;

    class ViewPathTest extends TestCase {

        // View-name equivalence (same app view, four references)

        /** @dataProvider viewReferences */
        public function testResolvesTheReferenceToTheViewName(string $reference, string $expected): void {
            $this->assertSame($expected, Response::viewName($reference));
        }

        public static function viewReferences(): array {
            return [
                'bare "core/render"' => ["core/render", "core/render"],
                '"core/render.blade.php"' => ["core/render.blade.php", "core/render"],
                'legacy "core/render.php"' => ["core/render.php", "core/render"],
                // Katana resolves the dots, so they must reach it untouched
                'dotted "core.render"' => ["core.render", "core.render"],
            ];
        }

        /** @dataProvider unusualReferences */
        public function testNormalizesUnusualReferences(string $reference, string $expected): void {
            $this->assertSame($expected, Response::viewName($reference));
        }

        public static function unusualReferences(): array {
            return [
                "only one suffix is removed" => ["core/render.blade.php.php", "core/render.blade.php"],
                "a blade suffix is removed as a whole" => ["render.blade.php", "render"],
                "a php segment before the blade suffix stays" => ["name.php.blade.php", "name.php"],
                "an extension in another case stays" => ["core/render.PHP", "core/render.PHP"],
                "an extension that is only a prefix of the ending stays" => ["core/render.phpx", "core/render.phpx"],
                "a half blade suffix is only a name" => ["core/render.blade", "core/render.blade"],
                "trailing whitespace is dropped before the suffix is looked at" => ["core/render.php \n", "core/render"],
                "trailing whitespace is dropped from a bare name" => ["core/render  ", "core/render"],
                "leading whitespace stays" => ["  core/render", "  core/render"],
                "a nested name keeps its folders" => ["layout/admin/side.blade.php", "layout/admin/side"],
            ];
        }
    }

?>
