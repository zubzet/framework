<?php

    namespace ZubZet\Framework\Registry;

    use ZubZet\Framework\Support\StaticCache;
    use ZubZet\Framework\ErrorHandling\DebugBar\DebugBarBridge;

    /**
     * Central resolver for everything the framework loads by convention.
     * Precedence everywhere: userspace -> modules (ordered) -> framework.
     *
     * find() results are memoized per request so one name maps to exactly one
     * file for the whole request, including error-page reroutes.
     */
    final class Registry {

        /** @return string[] Ordered roots where $kind can live. */
        public static function paths(string $kind): array {
            return array_map(fn($labeled) => $labeled[1], self::labeledPaths(Kinds::get($kind)));
        }

        /**
         * @return array{0: string, 1: string}[] Ordered [origin, root] pairs;
         * origin is "userspace", "module:<package>", or "framework". Fuels the
         * debug bar's resolution provenance.
         */
        private static function labeledPaths(Kind $definition): array {
            $paths = [["userspace", $definition->userspaceRoot()]];
            foreach(Modules::roots() as $package => $root) {
                $paths[] = ["module:$package", "$root/{$definition->moduleSubPath}"];
            }
            if(!is_null($definition->frameworkSubPath)) {
                $paths[] = ["framework", config("z_framework_root") . $definition->frameworkSubPath];
            }
            return $paths;
        }

        /**
         * @return string[] Module roots only, for call sites that keep a
         * historical non-standard order (assets) or treat modules as a
         * distinct set (migrations).
         */
        public static function moduleRoots(string $kind): array {
            $definition = Kinds::get($kind);
            $paths = [];
            foreach(Modules::roots() as $root) {
                $paths[] = "$root/{$definition->moduleSubPath}";
            }
            return $paths;
        }

        /** @return string[] Every file of $kind across all roots, in precedence order. */
        public static function files(string $kind): array {
            $definition = Kinds::get($kind);
            $files = [];
            foreach(self::labeledPaths($definition) as [$origin, $root]) {
                if(!is_dir($root)) continue;
                if($definition->deepFiles) {
                    $rootFiles = RootIndex::for($root, $definition->extensions)->all();
                } else {
                    // Flat and alphabetical, matching the historical glob().
                    $rootFiles = glob(rtrim($root, "/") . "/*.php");
                }
                foreach($rootFiles as $file) {
                    DebugBarBridge::collectResolution($definition->name, basename($file), $origin, $file);
                }
                $files = array_merge($files, $rootFiles);
            }
            return $files;
        }

        /**
         * Locates one file of $kind in two passes over the ordered roots.
         *
         * Pass 1 flat-probes every root in precedence order; it is byte
         * identical to the historical lookup, so every name that resolved
         * before this abstraction resolves to the same file at the same cost.
         * Pass 2 runs only when every root missed flat, and only for bare
         * names: the lazy recursive index per root, shallowest match first.
         * Nested files are therefore only reachable for names that previously
         * resolved nowhere; they can never shadow a flat file in a later root.
         */
        public static function find(string $kind, string $name): ?string {
            $cacheKey = "$kind:$name";
            $memoized = StaticCache::getOrNull("registry_find", $cacheKey);
            if(!is_null($memoized)) return $memoized;

            $definition = Kinds::get($kind);
            $roots = array_map(
                fn($labeled) => [$labeled[0], rtrim($labeled[1], "/") . "/"],
                self::labeledPaths($definition),
            );

            foreach($roots as [$origin, $root]) {
                foreach($definition->extensions as $extension) {
                    if(file_exists($root . $name . $extension)) {
                        DebugBarBridge::collectResolution($kind, $name, $origin, $root . $name . $extension);
                        return StaticCache::set("registry_find", $cacheKey, $root . $name . $extension);
                    }
                }
            }

            // Explicit paths (e.g. model dot notation) address one exact
            // location per root and never fall back to the index.
            $isExplicitPath = false !== strpbrk($name, "/\\");
            if($isExplicitPath || !$definition->deepFind) return null;

            foreach($roots as [$origin, $root]) {
                $hit = RootIndex::for($root, $definition->extensions)->find($name);
                if(!is_null($hit)) {
                    DebugBarBridge::collectResolution($kind, $name, "$origin (recursive)", $hit);
                    return StaticCache::set("registry_find", $cacheKey, $hit);
                }
            }
            return null;
        }
    }

?>
