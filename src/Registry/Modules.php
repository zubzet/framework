<?php

    namespace ZubZet\Framework\Registry;

    use Composer\InstalledVersions;
    use ZubZet\Framework\Support\StaticCache;

    /**
     * Discovery and ordering of installed modules (composer type "zubzet-module").
     *
     * Order: packages listed in the "modules" ini key (comma-separated) first,
     * in listed order; unlisted installed modules after, in composer installed
     * order. Listed-but-not-installed names are ignored, so a stale ini entry
     * only affects ordering, never discovery.
     */
    final class Modules {

        public const PACKAGE_TYPE = "zubzet-module";

        /** @return string[] Ordered composer package names of installed modules. */
        public static function packages(): array {
            $cached = StaticCache::getOrNull("registry", "module_packages");
            if(!is_null($cached)) return $cached;

            $installed = array_values(array_unique(
                InstalledVersions::getInstalledPackagesByType(self::PACKAGE_TYPE)
            ));

            $listed = array_filter(array_map("trim", explode(",", (string) config("modules", default: ""))));
            $disabled = array_filter(array_map("trim", explode(",", (string) config("modules_disabled", default: ""))));

            $ordered = [];
            foreach([...$listed, ...$installed] as $package) {
                if(in_array($package, $disabled, true)) continue;
                if(!in_array($package, $installed, true)) continue;
                if(in_array($package, $ordered, true)) continue;
                $ordered[] = $package;
            }

            return StaticCache::set("registry", "module_packages", $ordered);
        }

        /** @return string[] Ordered absolute module install roots, keyed by package name. */
        public static function roots(): array {
            $cached = StaticCache::getOrNull("registry", "module_roots");
            if(!is_null($cached)) return $cached;

            $roots = [];
            foreach(self::packages() as $package) {
                $root = InstalledVersions::getInstallPath($package);
                // Metapackages resolve to no install path.
                if(is_null($root)) continue;
                $roots[$package] = rtrim($root, "/\\");
            }

            return StaticCache::set("registry", "module_roots", $roots);
        }
    }

?>
