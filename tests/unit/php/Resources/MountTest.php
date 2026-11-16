<?php

    namespace Tests\Resources;

    use RuntimeException;
    use Tests\TestCase;
    use ValueError;
    use ZubZet\Framework\Resources\Mount;

    class MountTest extends TestCase {

        private const SENTINEL = "This file is used for security tests.";

        // Escapes out of the source

        public function testRejectsSiblingPrefixTraversalOutOfTheRegisteredSource(): void {
            $base = $this->base();
            mkdir($base . "webroot");
            // Shares the prefix "webroot" with the source folder, but lies outside of it
            file_put_contents($base . "webroot_security.txt", self::SENTINEL);

            $mount = new Mount($base . "webroot/");

            $this->assertThrows(
                RuntimeException::class,
                "Invalid asset path: " . $base . "webroot_security.txt",
                fn() => $mount->resolve("../webroot_security.txt"),
            );
        }

        public function testRejectsTraversalIntoTheParentFolder(): void {
            $base = $this->base();
            mkdir($base . "webroot");
            file_put_contents($base . "secret.txt", self::SENTINEL);

            $mount = new Mount($base . "webroot");

            $this->assertThrows(
                RuntimeException::class,
                "Invalid asset path: " . $base . "secret.txt",
                fn() => $mount->resolve("../secret.txt"),
            );
        }

        public function testReturnsNullWhenTheTraversalTargetDoesNotExist(): void {
            $base = $this->base();
            mkdir($base . "webroot");

            $this->assertNull((new Mount($base . "webroot"))->resolve("../missing.txt"));
        }

        public function testRejectsSymlinksInsideSourceThatPointOutside(): void {
            $base = $this->base();
            mkdir($base . "webroot/assets", 0777, true);
            file_put_contents($base . "webroot_security.txt", self::SENTINEL);
            symlink("../../webroot_security.txt", $base . "webroot/assets/outside_symlink");

            $mount = new Mount($base . "webroot/assets/");

            $this->assertThrows(
                RuntimeException::class,
                "Invalid asset path: " . $base . "webroot_security.txt",
                fn() => $mount->resolve("outside_symlink"),
            );
        }

        public function testAllowsSymlinksThatStayInsideTheSource(): void {
            $base = $this->base();
            mkdir($base . "webroot/assets", 0777, true);
            file_put_contents($base . "webroot/assets/assets.txt", "inside");
            symlink("assets.txt", $base . "webroot/assets/inside_symlink");

            $mount = new Mount($base . "webroot/assets/");

            $this->assertSame($base . "webroot/assets/assets.txt", $mount->resolve("inside_symlink"));
        }

        public function testRejectsPathsWithANullByteSuffix(): void {
            $base = $this->base();
            mkdir($base . "webroot/assets", 0777, true);
            file_put_contents($base . "webroot/assets/assets.txt", "A file inside the assets directory");

            $mount = new Mount($base . "webroot/assets/");

            // Whether it is refused or not found, the file behind the null byte must not come back
            $resolved = null;
            try {
                $resolved = $mount->resolve("assets.txt\0.png");
            } catch(ValueError | RuntimeException $refusal) {
                // Refused
            }
            $this->assertNull($resolved);
        }

        // Files inside the source

        public function testReturnsTheFullPathOfAFileInsideTheSource(): void {
            $base = $this->base();
            mkdir($base . "webroot/assets/css", 0777, true);
            file_put_contents($base . "webroot/assets/assets.txt", "inside");
            file_put_contents($base . "webroot/assets/css/site.css", "body {}");

            $mount = new Mount($base . "webroot/assets/");

            $this->assertSame($base . "webroot/assets/assets.txt", $mount->resolve("assets.txt"));
            $this->assertSame($base . "webroot/assets/css/site.css", $mount->resolve("css/site.css"));
            $this->assertSame($base . "webroot/assets/css/site.css", $mount->resolve("css/../css/site.css"));
        }

        public function testReturnsNullForAFolder(): void {
            $base = $this->base();
            mkdir($base . "webroot/assets", 0777, true);

            $this->assertNull((new Mount($base . "webroot"))->resolve("assets"));
        }

        public function testReturnsNullForAMissingFileAndForAMissingSource(): void {
            $base = $this->base();
            mkdir($base . "webroot");

            $this->assertNull((new Mount($base . "webroot"))->resolve("missing.txt"));
            $this->assertNull((new Mount($base . "no-such-folder"))->resolve("missing.txt"));
        }

        // The URL prefix

        public function testStripsTheUrlPrefixBeforeLookingInTheSource(): void {
            $base = $this->base();
            mkdir($base . "webroot");
            file_put_contents($base . "webroot/site.css", "body {}");

            $mount = new Mount($base . "webroot", "/css/");

            $this->assertSame($base . "webroot/site.css", $mount->resolve("css/site.css"));
        }

        public function testIgnoresPathsOutsideTheUrlPrefix(): void {
            $base = $this->base();
            mkdir($base . "webroot");
            file_put_contents($base . "webroot/site.css", "body {}");
            file_put_contents($base . "webroot/x", "x");

            $mount = new Mount($base . "webroot", "css");

            $this->assertNull($mount->resolve("site.css"));
            $this->assertNull($mount->resolve("css-other/x"));
            $this->assertNull($mount->resolve("css"));
        }

        // Resolved, so the expected paths equal what realpath() returns when the temporary folder is behind a link
        private function base(): string {
            return realpath($this->temporaryDirectory()) . "/";
        }
    }

?>
