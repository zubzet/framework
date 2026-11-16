<?php

    namespace Tests\Resources;

    use PHPUnit\Framework\TestCase;
    use ZubZet\Framework\Resources\MimeType;

    class MimeTypeTest extends TestCase {

        /** @dataProvider assetProvider */
        public function testKnowsTheAssetsTheFrameworkShips(string $path, string $expected): void {
            $this->assertSame($expected, MimeType::fromPath($path));
        }

        public function assetProvider(): array {
            return [
                "script" => ["Z.js", "application/javascript"],
                "stylesheet" => ["css/bootstrap.min.css", "text/css"],
                "source map" => ["css/bootstrap.min.css.map", "application/json"],
                "json" => ["data/config.json", "application/json"],
                "svg" => ["img/logo.svg", "image/svg+xml"],
                "png" => ["img/logo.png", "image/png"],
                "jpg" => ["img/photo.jpg", "image/jpeg"],
                "jpeg" => ["img/photo.jpeg", "image/jpeg"],
                "icon" => ["favicon.ico", "image/x-icon"],
                "woff2" => ["webfonts/fa-solid-900.woff2", "font/woff2"],
                "ttf" => ["webfonts/fa-solid-900.ttf", "font/ttf"],
                "pdf" => ["docs/manual.pdf", "application/pdf"],
            ];
        }

        public function testIgnoresTheCaseOfTheExtension(): void {
            $this->assertSame("image/png", MimeType::fromPath("img/LOGO.PNG"));
        }

        public function testOnlyTheLastExtensionCounts(): void {
            $this->assertSame("application/javascript", MimeType::fromPath("jquery.min.js"));
            $this->assertSame("text/css", MimeType::fromPath("/var/www/v1.2/theme.css"));
        }

        public function testFallsBackForAnUnknownExtension(): void {
            $this->assertSame("application/octet-stream", MimeType::fromPath("archive.unknown"));
        }

        public function testFallsBackWithoutAnExtension(): void {
            $this->assertSame("application/octet-stream", MimeType::fromPath("unknown_mime"));
            $this->assertSame("application/octet-stream", MimeType::fromPath("/var/www/v1.2/Makefile"));
        }
    }

?>
