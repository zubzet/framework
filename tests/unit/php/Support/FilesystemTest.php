<?php

    namespace Tests\Support;

    use PHPUnit\Framework\TestCase;
    use ZubZet\Framework\Support\Filesystem;

    class FilesystemTest extends TestCase {

        private string $root;

        protected function setUp(): void {
            $this->root = sys_get_temp_dir() . "/zubzet-filesystem-" . uniqid() . "/";
            mkdir($this->root);
        }

        protected function tearDown(): void {
            Filesystem::remove($this->root);
        }

        public function testRemovesAFile(): void {
            file_put_contents($this->root . "file.txt", "a");

            Filesystem::remove($this->root . "file.txt");

            $this->assertFileDoesNotExist($this->root . "file.txt");
        }

        public function testRemovesAnEmptyDirectory(): void {
            mkdir($this->root . "empty");

            Filesystem::remove($this->root . "empty");

            $this->assertDirectoryDoesNotExist($this->root . "empty");
        }

        public function testRemovesADirectoryWithEverythingInIt(): void {
            mkdir($this->root . "tree/nested/deeper", 0755, true);
            file_put_contents($this->root . "tree/a.txt", "a");
            file_put_contents($this->root . "tree/.hidden", "b");
            file_put_contents($this->root . "tree/nested/deeper/c.txt", "c");

            Filesystem::remove($this->root . "tree");

            $this->assertDirectoryDoesNotExist($this->root . "tree");
        }

        public function testATrailingSlashMakesNoDifference(): void {
            mkdir($this->root . "tree");
            file_put_contents($this->root . "tree/a.txt", "a");

            Filesystem::remove($this->root . "tree/");

            $this->assertDirectoryDoesNotExist($this->root . "tree");
        }

        public function testLeavesTheSiblingsAlone(): void {
            mkdir($this->root . "tree");
            mkdir($this->root . "sibling");
            file_put_contents($this->root . "sibling/a.txt", "a");

            Filesystem::remove($this->root . "tree");

            $this->assertFileExists($this->root . "sibling/a.txt");
        }

        public function testAMissingPathIsLeftAlone(): void {
            Filesystem::remove($this->root . "missing");

            $this->assertDirectoryExists($this->root);
        }

        public function testRemovesALinkButNotItsTarget(): void {
            mkdir($this->root . "target");
            file_put_contents($this->root . "target/a.txt", "a");
            symlink($this->root . "target", $this->root . "link");

            Filesystem::remove($this->root . "link");

            $this->assertFalse(is_link($this->root . "link"));
            $this->assertFileExists($this->root . "target/a.txt");
        }

        public function testDoesNotDescendIntoALinkedDirectory(): void {
            mkdir($this->root . "target");
            file_put_contents($this->root . "target/a.txt", "a");
            mkdir($this->root . "tree");
            symlink($this->root . "target", $this->root . "tree/link");

            Filesystem::remove($this->root . "tree");

            $this->assertDirectoryDoesNotExist($this->root . "tree");
            $this->assertFileExists($this->root . "target/a.txt");
        }

        public function testRemovesABrokenLink(): void {
            symlink($this->root . "missing", $this->root . "link");

            Filesystem::remove($this->root . "link");

            $this->assertFalse(is_link($this->root . "link"));
        }

        public function testMakesADirectoryWithItsParents(): void {
            Filesystem::mkdir($this->root . "a/b/c");

            $this->assertDirectoryExists($this->root . "a/b/c");
        }

        public function testMakingAnExistingDirectoryKeepsItsContent(): void {
            mkdir($this->root . "existing");
            file_put_contents($this->root . "existing/a.txt", "a");

            Filesystem::mkdir($this->root . "existing");

            $this->assertFileExists($this->root . "existing/a.txt");
        }

        public function testMakingADirectoryThrowsWhenAFileIsInTheWay(): void {
            file_put_contents($this->root . "file", "a");

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage("Could not create the directory '" . $this->root . "file/sub'");

            Filesystem::mkdir($this->root . "file/sub");
        }

        public function testCopiesAFileAndCreatesTheParentsOfTheTarget(): void {
            file_put_contents($this->root . "file.txt", "content");

            Filesystem::copy($this->root . "file.txt", $this->root . "a/b/copy.txt");

            $this->assertSame("content", file_get_contents($this->root . "a/b/copy.txt"));
            $this->assertFileExists($this->root . "file.txt");
        }

        public function testMirrorsADirectoryWithEverythingInIt(): void {
            mkdir($this->root . "tree/nested/deeper", 0755, true);
            mkdir($this->root . "tree/empty");
            file_put_contents($this->root . "tree/a.txt", "a");
            file_put_contents($this->root . "tree/.hidden", "b");
            file_put_contents($this->root . "tree/nested/deeper/c.txt", "c");

            Filesystem::mirror($this->root . "tree/", $this->root . "copy/");

            $this->assertSame("a", file_get_contents($this->root . "copy/a.txt"));
            $this->assertSame("b", file_get_contents($this->root . "copy/.hidden"));
            $this->assertSame("c", file_get_contents($this->root . "copy/nested/deeper/c.txt"));
            $this->assertDirectoryExists($this->root . "copy/empty");
        }

        public function testMirroringMergesIntoAnExistingDirectory(): void {
            mkdir($this->root . "tree");
            file_put_contents($this->root . "tree/a.txt", "new");
            mkdir($this->root . "copy");
            file_put_contents($this->root . "copy/a.txt", "old");
            file_put_contents($this->root . "copy/kept.txt", "kept");
            touch($this->root . "copy/a.txt", time() - 60);

            Filesystem::mirror($this->root . "tree", $this->root . "copy");

            $this->assertSame("new", file_get_contents($this->root . "copy/a.txt"));
            $this->assertSame("kept", file_get_contents($this->root . "copy/kept.txt"));
        }

        public function testMirroringKeepsNewerFilesUnlessToldToOverride(): void {
            mkdir($this->root . "tree");
            file_put_contents($this->root . "tree/a.txt", "origin");
            touch($this->root . "tree/a.txt", time() - 60);
            mkdir($this->root . "copy");
            file_put_contents($this->root . "copy/a.txt", "newer");

            Filesystem::mirror($this->root . "tree", $this->root . "copy");
            $this->assertSame("newer", file_get_contents($this->root . "copy/a.txt"));

            Filesystem::mirror($this->root . "tree", $this->root . "copy", null, ["override" => true]);
            $this->assertSame("origin", file_get_contents($this->root . "copy/a.txt"));
        }

        public function testMirroringDeletesWhatTheOriginDoesNotHaveWhenAsked(): void {
            mkdir($this->root . "tree");
            file_put_contents($this->root . "tree/a.txt", "a");
            mkdir($this->root . "copy/obsolete/deeper", 0755, true);
            file_put_contents($this->root . "copy/obsolete/deeper/b.txt", "b");
            file_put_contents($this->root . "copy/c.txt", "c");

            Filesystem::mirror($this->root . "tree", $this->root . "copy", null, ["delete" => true]);

            $this->assertSame(["a.txt"], array_values(array_diff(scandir($this->root . "copy"), [".", ".."])));
        }

        public function testMirroringCopiesWhatALinkPointsToWhenAsked(): void {
            mkdir($this->root . "target");
            file_put_contents($this->root . "target/a.txt", "a");
            mkdir($this->root . "tree");
            symlink($this->root . "target", $this->root . "tree/link");

            Filesystem::mirror($this->root . "tree", $this->root . "copy", null, ["copy_on_windows" => true]);

            $this->assertFalse(is_link($this->root . "copy/link"));
            $this->assertSame("a", file_get_contents($this->root . "copy/link/a.txt"));
        }

        public function testMirroringOnlyCopiesWhatTheIteratorYields(): void {
            mkdir($this->root . "tree");
            file_put_contents($this->root . "tree/a.txt", "a");
            file_put_contents($this->root . "tree/b.log", "b");

            $onlyText = new \CallbackFilterIterator(
                new \FilesystemIterator($this->root . "tree"),
                fn(\SplFileInfo $file) => "txt" === $file->getExtension(),
            );

            Filesystem::mirror($this->root . "tree", $this->root . "copy", $onlyText);

            $this->assertSame(["a.txt"], array_values(array_diff(scandir($this->root . "copy"), [".", ".."])));
        }

        public function testMirrorsALinkAsALink(): void {
            mkdir($this->root . "target");
            file_put_contents($this->root . "target/a.txt", "a");
            mkdir($this->root . "tree");
            symlink($this->root . "target", $this->root . "tree/link");

            Filesystem::mirror($this->root . "tree", $this->root . "copy");

            $this->assertTrue(is_link($this->root . "copy/link"));
            $this->assertSame($this->root . "target", readlink($this->root . "copy/link"));
        }

        public function testCopyingReplacesAnOlderFile(): void {
            file_put_contents($this->root . "file.txt", "new");
            file_put_contents($this->root . "copy.txt", "old");
            touch($this->root . "copy.txt", time() - 60);

            Filesystem::copy($this->root . "file.txt", $this->root . "copy.txt");

            $this->assertSame("new", file_get_contents($this->root . "copy.txt"));
        }

        public function testCopyingKeepsANewerFileUnlessToldToOverwrite(): void {
            file_put_contents($this->root . "file.txt", "origin");
            touch($this->root . "file.txt", time() - 60);
            file_put_contents($this->root . "copy.txt", "newer");

            Filesystem::copy($this->root . "file.txt", $this->root . "copy.txt");
            $this->assertSame("newer", file_get_contents($this->root . "copy.txt"));

            Filesystem::copy($this->root . "file.txt", $this->root . "copy.txt", true);
            $this->assertSame("origin", file_get_contents($this->root . "copy.txt"));
        }

        public function testACopyKeepsTheModificationTimeAndTheMode(): void {
            file_put_contents($this->root . "script.sh", "#!/bin/sh");
            chmod($this->root . "script.sh", 0755);
            touch($this->root . "script.sh", 1700000000);

            Filesystem::copy($this->root . "script.sh", $this->root . "copy.sh");

            $this->assertSame(1700000000, filemtime($this->root . "copy.sh"));
            $this->assertSame(0755 & ~umask(), fileperms($this->root . "copy.sh") & 0777);
        }

        public function testCopyingAMissingSourceThrows(): void {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage("Could not copy '" . $this->root . "missing': the file does not exist");

            Filesystem::copy($this->root . "missing", $this->root . "copy");
        }

        public function testCopyingADirectoryThrows(): void {
            mkdir($this->root . "tree");

            $this->expectException(\RuntimeException::class);

            Filesystem::copy($this->root . "tree", $this->root . "copy");
        }

        public function testMirroringAFileThrows(): void {
            file_put_contents($this->root . "file.txt", "a");

            $this->expectException(\RuntimeException::class);

            Filesystem::mirror($this->root . "file.txt", $this->root . "copy");
        }

        public function testMakesAndRemovesSeveralPathsAtOnce(): void {
            Filesystem::mkdir([$this->root . "a/b", $this->root . "c"]);

            $this->assertDirectoryExists($this->root . "a/b");
            $this->assertDirectoryExists($this->root . "c");

            file_put_contents($this->root . "file.txt", "a");
            Filesystem::remove([$this->root . "a", $this->root . "file.txt", $this->root . "missing"]);

            $this->assertSame(["c"], array_values(array_diff(scandir($this->root), [".", ".."])));
        }

        public function testRemovesWhatAnIteratorYields(): void {
            mkdir($this->root . "tree");
            file_put_contents($this->root . "tree/a.txt", "a");
            file_put_contents($this->root . "tree/b.txt", "b");

            Filesystem::remove(new \FilesystemIterator($this->root . "tree"));

            $this->assertSame([], array_values(array_diff(scandir($this->root . "tree"), [".", ".."])));
        }

        public function testTheMethodsAlsoWorkOnAnInstance(): void {
            $filesystem = new Filesystem();

            $filesystem->mkdir($this->root . "a/b");
            $this->assertDirectoryExists($this->root . "a/b");

            $filesystem->remove($this->root . "a");
            $this->assertDirectoryDoesNotExist($this->root . "a");
        }

        public function testThrowsWhenAnEntryCannotBeRemoved(): void {
            // Root ignores directory permissions, so the failure cannot be provoked
            if(0 === posix_geteuid()) $this->markTestSkipped("Runs as root.");

            mkdir($this->root . "locked");
            file_put_contents($this->root . "locked/a.txt", "a");
            chmod($this->root . "locked", 0555);

            try {
                Filesystem::remove($this->root . "locked");
                $this->fail("Removing from a read-only directory did not throw.");
            } catch(\RuntimeException $e) {
                $this->assertStringContainsString($this->root . "locked/a.txt", $e->getMessage());
            } finally {
                chmod($this->root . "locked", 0755);
            }
        }
    }

?>
