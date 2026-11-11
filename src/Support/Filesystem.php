<?php

    namespace ZubZet\Framework\Support;

    /**
     * The part of symfony/filesystem the framework needs, with the signatures and the behavior of
     * its Filesystem class for local files. The methods are static, PHP also accepts them on an
     * instance: (new Filesystem())->remove($path) works like Filesystem::remove($path).
     */
    final class Filesystem {

        /**
         * Creates directories with every missing parent. A directory that already exists is left alone,
         * also when another process creates it at the same moment.
         * @param string|iterable $dirs One path or several
         * @param int $mode The permissions, lowered by the umask like with PHP's mkdir()
         * @throws \RuntimeException When a directory cannot be created
         */
        public static function mkdir(string|iterable $dirs, int $mode = 0777): void {
            foreach(self::toPaths($dirs) as $dir) {
                if(is_dir($dir)) continue;

                // Checked again: losing the race against another process is not a failure
                if(!@mkdir($dir, $mode, true) && !is_dir($dir)) {
                    throw self::failure("create the directory", $dir);
                }
            }
        }

        /**
         * Copies one file with its permissions and its modification time. Missing parent directories
         * of the target are created. An existing target is only replaced when the origin is newer,
         * or always with $overwriteNewerFiles. A directory is copied with mirror().
         * @throws \RuntimeException When the origin is not a file or cannot be copied
         */
        public static function copy(string $originFile, string $targetFile, bool $overwriteNewerFiles = false): void {
            if(!is_file($originFile)) {
                throw new \RuntimeException("Could not copy '$originFile': the file does not exist");
            }

            self::mkdir(dirname($targetFile));

            $isUpToDate = !$overwriteNewerFiles
                && is_file($targetFile)
                && filemtime($originFile) <= filemtime($targetFile);
            if($isUpToDate) return;

            if(!@copy($originFile, $targetFile)) throw self::failure("copy", $originFile);

            // Like `cp`: the mode of the origin lowered by the umask, and its modification time
            @chmod($targetFile, fileperms($originFile) & 0777 & ~umask());
            @touch($targetFile, filemtime($originFile));
        }

        /**
         * Copies a directory with everything in it. Missing parent directories of the target are
         * created, files are copied like with copy(), a link is copied as a link.
         * @param \Traversable|null $iterator The entries to copy as SplFileInfo, by default everything in the origin
         * @param array $options
         *   "override": replace files in the target that are newer than their origin (default false),
         *   "delete": remove what exists in the target but not in the origin (default false),
         *   "copy_on_windows": copy what a link points to instead of the link (default false)
         * @throws \RuntimeException When the origin is not a directory or an entry cannot be copied
         */
        public static function mirror(string $originDir, string $targetDir, ?\Traversable $iterator = null, array $options = []): void {
            $originDir = rtrim($originDir, "/\\");
            $targetDir = rtrim($targetDir, "/\\");

            if(!is_dir($originDir)) {
                throw new \RuntimeException("Could not mirror '$originDir': the directory does not exist");
            }

            if(($options["delete"] ?? false) && is_dir($targetDir)) {
                self::removeObsolete($originDir, $targetDir);
            }

            $copyLinkTargets = $options["copy_on_windows"] ?? false;

            if(is_null($iterator)) {
                $flags = \FilesystemIterator::SKIP_DOTS | ($copyLinkTargets ? \FilesystemIterator::FOLLOW_SYMLINKS : 0);
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($originDir, $flags),
                    \RecursiveIteratorIterator::SELF_FIRST,
                );
            }

            self::mkdir($targetDir);
            $created = [];

            foreach($iterator as $file) {
                // The target can lie within the origin, neither it nor what was just copied into it is copied again
                $isOwnOutput = $file->getPathname() === $targetDir
                    || $file->getRealPath() === $targetDir
                    || isset($created[$file->getRealPath()]);
                if($isOwnOutput) continue;

                $origin = $file->getPathname();
                $target = $targetDir . substr($origin, strlen($originDir));
                $created[$target] = true;

                if(!$copyLinkTargets && is_link($origin)) {
                    self::link(readlink($origin), $target);
                    continue;
                }

                if(is_dir($origin)) {
                    self::mkdir($target);
                    continue;
                }

                self::copy($origin, $target, $options["override"] ?? false);
            }
        }

        /**
         * Removes files, links and directories with everything in them.
         * A path that does not exist is left alone. A link is removed itself, its target is never touched.
         * @param string|iterable $files One path or several
         * @throws \RuntimeException When an entry cannot be removed
         */
        public static function remove(string|iterable $files): void {
            foreach(self::toPaths($files) as $file) {
                self::removePath($file);
            }
        }

        private static function removePath(string $path): void {
            // A link first: is_dir() follows it, and a broken one does not even exist for file_exists()
            if(is_link($path) || is_file($path)) {
                if(!@unlink($path)) throw self::failure("remove", $path);
                return;
            }

            if(!is_dir($path)) return;

            foreach(array_diff(scandir($path), [".", ".."]) as $entry) {
                self::removePath(rtrim($path, "/") . "/" . $entry);
            }

            if(!@rmdir($path)) throw self::failure("remove", $path);
        }

        // What mirror() removes with its "delete" option: everything in the target the origin does not have
        private static function removeObsolete(string $originDir, string $targetDir): void {
            $entries = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($targetDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach($entries as $entry) {
                $origin = $originDir . substr($entry->getPathname(), strlen($targetDir));
                if(file_exists($origin)) continue;

                self::removePath($entry->getPathname());
            }
        }

        // A link that already points to the same target is kept, another link is replaced
        private static function link(string $linkTarget, string $path): void {
            self::mkdir(dirname($path));

            if(is_link($path)) {
                if(readlink($path) === $linkTarget) return;
                self::removePath($path);
            }

            if(!@symlink($linkTarget, $path)) throw self::failure("link", $path);
        }

        /** @return string[] */
        private static function toPaths(string|iterable $paths): array {
            if(is_string($paths)) return [$paths];

            $result = [];
            foreach($paths as $path) {
                // An iterator over a directory yields SplFileInfo
                $result[] = (string) $path;
            }
            return $result;
        }

        private static function failure(string $action, string $path): \RuntimeException {
            $reason = error_get_last()["message"] ?? "unknown reason";
            return new \RuntimeException("Could not $action '$path': $reason");
        }
    }

?>
