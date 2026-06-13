<?php

    namespace ZubZet\Framework\Support;

    /**
     * Filesystem cache that hands out writable directories.
     *
     * Subsystems that manage their own files (e.g. BladeOne's compile
     * directory) can grab a writable subfolder via `directoryFor($name)`.
     */
    class FileCache {

        private string $directory;

        public function __construct(string $directory) {
            $this->directory = rtrim($directory, '/\\');
            $this->ensureDirectory($this->directory);
        }

        public function directoryFor(string $name): string {
            $path = $this->directory . DIRECTORY_SEPARATOR . $name;
            $this->ensureDirectory($path);
            return $path;
        }

        private function ensureDirectory(string $path): void {
            if(is_dir($path)) return;
            if(@mkdir($path, 0775, true)) return;
            if(is_dir($path)) return;
            throw new \RuntimeException("Cache directory '$path' could not be created.");
        }

    }

?>
