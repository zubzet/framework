<?php

    namespace ZubZet\Framework\Resources;

    /**
     * Maps an asset URL onto a directory on disk, optionally scoped to a URL
     * prefix. All filesystem work is deferred to resolve(); construction is
     * just a string trim.
     */
    final class Mount {

        public string $sourceRoot;
        public string $urlPrefix;

        public function __construct(string $sourceRoot, string $urlPrefix = "") {
            $this->sourceRoot = $sourceRoot;
            $this->urlPrefix = trim($urlPrefix, "/");
        }

        /** Returns the full file path or null if it does not belong to this mount. */
        public function resolve(string $assetPath): ?string {
            $subDirectory = $assetPath;

            if(!empty($this->urlPrefix)) {
                $needle = "{$this->urlPrefix}/";
                if(!str_starts_with($subDirectory, $needle)) return null;
                $subDirectory = substr($subDirectory, strlen($needle));
            }

            $root = realpath($this->sourceRoot);
            if(false === $root) return null;

            $fullPath = realpath("{$this->sourceRoot}/{$subDirectory}");
            if(false === $fullPath) return null;

            // Reject traversal escapes. Trailing separator forces an exact
            // boundary so siblings sharing a prefix are rejected.
            if(!str_starts_with($fullPath, $root . DIRECTORY_SEPARATOR)) {
                throw new \RuntimeException("Invalid asset path: $fullPath");
            }

            if(!is_file($fullPath)) return null;
            if(!is_readable($fullPath)) return null;
            return $fullPath;
        }
    }

?>
