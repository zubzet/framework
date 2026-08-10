<?php

    namespace ZubZet\Framework\Registry;

    use ZubZet\Framework\Support\StaticCache;

    /**
     * Lazy recursive filename index of one root directory. This is the slow
     * path behind Registry::find(): it is only built after every direct probe
     * missed, and is memoized per (root, extensions) for the request.
     *
     * Fulfils the abstraction asked for by z_migrationModel::getFiles()'s
     * "@TODO: abstract RecursiveIteratorIterator".
     */
    final class RootIndex {

        /** @var array<string, string[]> filename (no extension) => sorted relative paths */
        private array $byName = [];

        /** @var string[] all indexed files, absolute, sorted */
        private array $all = [];

        private function __construct(
            private string $root,
            private array $extensions,
        ) {
            $this->build();
        }

        public static function for(string $root, array $extensions): self {
            $root = rtrim($root, "/");
            $key = $root . "|" . implode(",", $extensions);

            $index = StaticCache::getOrNull("registry_index", $key);
            if(is_null($index)) {
                $index = StaticCache::set("registry_index", $key, new self($root, $extensions));
            }
            return $index;
        }

        /** @return string[] Absolute paths of every indexed file, sorted. */
        public function all(): array {
            return $this->all;
        }

        /**
         * Locates a bare name inside this root: shallowest match first, ties
         * broken by byte-order comparison of the relative path, so resolution
         * is identical across filesystems.
         */
        public function find(string $name): ?string {
            if(!isset($this->byName[$name])) return null;

            $candidates = $this->byName[$name];
            usort($candidates, function(string $a, string $b) {
                $depth = substr_count($a, "/") <=> substr_count($b, "/");
                if(0 !== $depth) return $depth;
                return strcmp($a, $b);
            });

            return $this->root . "/" . $candidates[0];
        }

        private function build(): void {
            if(!is_dir($this->root)) return;

            // CATCH_GET_CHILD: an unreadable subdirectory is skipped instead
            // of turning the whole lookup into an exception.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach($iterator as $file) {
                if($file->isDir()) continue;

                $path = $file->getPathname();
                $matchedExtension = null;
                foreach($this->extensions as $extension) {
                    if(str_ends_with($path, $extension)) {
                        $matchedExtension = $extension;
                        break;
                    }
                }
                if(is_null($matchedExtension) && !empty($this->extensions)) continue;

                $relative = str_replace("\\", "/", substr($path, strlen($this->root) + 1));
                $name = substr($relative, 0, strlen($relative) - strlen((string) $matchedExtension));
                $name = basename($name);

                $this->byName[$name][] = $relative;
                $this->all[] = $this->root . "/" . $relative;
            }

            sort($this->all);
        }
    }

?>
