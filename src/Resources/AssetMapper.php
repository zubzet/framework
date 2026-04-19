<?php

    namespace ZubZet\Framework\Resources;

    use League\MimeTypeDetection\FinfoMimeTypeDetector;

    class AssetMapper {

        /** @var array<int, array{prefix: string, dir: string}> */
        private array $sources = [];

        private ?ImportMap $importMap = null;

        public function __construct() {
            $this->registerFrameworkSource();
            $this->registerUserSource();
        }

        public function importMap(): ImportMap {
            if (null === $this->importMap) {
                $this->importMap = new ImportMap($this);
                $importmapConfig = getcwd() . "/importmap.php";
                $this->importMap->loadFromFile($importmapConfig);
            }
            return $this->importMap;
        }

        private function registerFrameworkSource(): void {
            $frameworkAssets = config("z_framework_root") . "IncludedComponents/assets/";
            if (is_dir($frameworkAssets)) {
                $this->registerSource($frameworkAssets);
            }
        }

        private function registerUserSource(): void {
            $userAssets = getcwd() . "/assets/";
            if (is_dir($userAssets)) {
                $this->prependSource($userAssets);
            }
        }

        /**
         * Register a directory as an asset source.
         * When $prefix is empty, logical paths resolve directly under $directory.
         * When $prefix is set (e.g. "bootstrap"), only logical paths starting with
         * "$prefix/" are resolved, and the remainder is appended to $directory.
         */
        public function registerSource(string $directory, string $prefix = ''): void {
            $this->sources[] = $this->normalizeSource($directory, $prefix);
        }

        /**
         * Same as registerSource, but inserts the source at the highest priority.
         */
        public function prependSource(string $directory, string $prefix = ''): void {
            array_unshift($this->sources, $this->normalizeSource($directory, $prefix));
        }

        /**
         * Back-compat alias for the previous AssetProxy API.
         */
        public function registerWebRootSource(string $webRootSource): void {
            $this->registerSource($webRootSource);
        }

        /**
         * Discover asset sources from Composer packages.
         *
         * Reads asset mappings from two composer.json files:
         *  - The application's composer.json at $projectRoot (user overrides, highest priority)
         *  - The framework's own composer.json (default essentials mapping)
         *
         * Both declare the mapping under "extra.zubzet-assets" as prefix => vendor-path.
         *
         * Then scans each installed package for "extra.zubzet-asset-dir" which
         * registers that package under its composer name as the prefix.
         */
        public function registerComposerPackages(?string $projectRoot = null): void {
            $projectRoot ??= getcwd();
            $vendorDir = $this->locateVendorDir($projectRoot);

            $registeredPrefixes = [];
            $overriddenPackages = [];

            $composerFiles = array_filter([
                $projectRoot . "/composer.json",
                $this->frameworkComposerFile(),
            ], fn($f) => null !== $f && is_file($f));

            foreach ($composerFiles as $composerFile) {
                $overrides = $this->readExtraAssets($composerFile);
                foreach ($overrides as $prefix => $relative) {
                    $prefix = (string) $prefix;
                    if (isset($registeredPrefixes[$prefix])) continue;

                    $relative = ltrim((string) $relative, "/");
                    $dir = $vendorDir . "/" . $relative;
                    if (!is_dir($dir)) continue;

                    $this->registerSource($dir, $prefix);
                    $registeredPrefixes[$prefix] = true;

                    $parts = explode("/", $relative);
                    if (count($parts) >= 2) {
                        $overriddenPackages[$parts[0] . "/" . $parts[1]] = true;
                    }
                }
            }

            foreach ($this->discoverPackageAssetDirs($vendorDir) as $package => $relative) {
                if (isset($overriddenPackages[$package])) continue;
                if (isset($registeredPrefixes[$package])) continue;
                $dir = $vendorDir . "/" . $package . "/" . ltrim($relative, "/");
                if (!is_dir($dir)) continue;
                $this->registerSource($dir, $package);
                $registeredPrefixes[$package] = true;
            }
        }

        /**
         * Resolve a logical path to a physical file, or null if not found.
         */
        public function resolve(string $logicalPath): ?string {
            $logicalPath = ltrim($logicalPath, "/");
            if ('' === $logicalPath) return null;

            foreach ($this->sources as $source) {
                $prefix = $source['prefix'];
                $dir = $source['dir'];

                if ('' === $prefix) {
                    $candidate = realpath($dir . '/' . $logicalPath);
                } else {
                    $needle = $prefix . '/';
                    if (!str_starts_with($logicalPath, $needle)) continue;
                    $remainder = substr($logicalPath, strlen($needle));
                    $candidate = realpath($dir . '/' . $remainder);
                }

                if (false === $candidate) continue;
                if (!str_starts_with($candidate, $dir)) continue;
                return $candidate;
            }

            return null;
        }

        /**
         * Build the public URL for a logical asset path (versioned).
         */
        public function url(string $logicalPath, bool $includeRoot = true): string {
            $logicalPath = ltrim($logicalPath, "/");
            $root = $includeRoot ? rtrim((string) zubzet()->rootFolder, "/") : "";
            $version = $this->version();
            return $root . "/_zubzet/asset-proxy/" . $logicalPath . "?v=" . $version;
        }

        /**
         * Serve an asset found at the logical path, or respond with 404.
         */
        public function serve(string $assetPath): void {
            $fullAssetPath = $this->resolve($assetPath);

            if (null === $fullAssetPath) {
                http_response_code(404);
                echo "Asset not found: " . e($assetPath);
                return;
            }

            $mimeDetector = new FinfoMimeTypeDetector();
            $mimeType = $mimeDetector->detectMimeTypeFromPath($fullAssetPath) ?? "application/octet-stream";

            header("Content-Type: $mimeType");
            readfile($fullAssetPath);
        }

        /**
         * @return array<int, array{prefix: string, dir: string}>
         */
        public function getSources(): array {
            return $this->sources;
        }

        private function normalizeSource(string $directory, string $prefix): array {
            $directory = rtrim($directory, "/");
            if (!is_dir($directory)) {
                throw new \InvalidArgumentException("Asset source is not a valid directory: $directory");
            }
            $real = realpath($directory);
            return [
                'prefix' => trim($prefix, "/"),
                'dir' => false === $real ? $directory : $real,
            ];
        }

        private function version(): string {
            $v = config("assetVersion", useDefault: true, default: "dev");
            return ("dev" === $v) ? (string) time() : (string) $v;
        }

        /**
         * @return array<string, string> prefix => vendor-relative path
         */
        private function readExtraAssets(string $composerFile): array {
            if (!is_file($composerFile)) return [];
            $composer = json_decode((string) file_get_contents($composerFile), true);
            $map = $composer["extra"]["zubzet-assets"] ?? [];
            return is_array($map) ? $map : [];
        }

        private function frameworkComposerFile(): ?string {
            $src = (string) config("z_framework_root", default: "");
            if ('' === $src) return null;
            return dirname(rtrim($src, "/\\")) . "/composer.json";
        }

        private function locateVendorDir(string $projectRoot): string {
            $env = getenv("COMPOSER_VENDOR_DIR");
            if (false !== $env && '' !== $env) {
                return rtrim($env, "/\\");
            }
            if (class_exists(\Composer\Autoload\ClassLoader::class)) {
                $file = (new \ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName();
                if (false !== $file) {
                    return dirname($file, 2);
                }
            }
            return rtrim($projectRoot, "/\\") . "/vendor";
        }

        /**
         * @return array<string, string> package-name => relative asset dir
         */
        private function discoverPackageAssetDirs(string $vendorDir): array {
            if (!is_dir($vendorDir)) return [];
            $found = [];
            foreach (glob($vendorDir . "/*/*/composer.json") ?: [] as $composerFile) {
                $composer = json_decode((string) file_get_contents($composerFile), true);
                if (!is_array($composer)) continue;
                $assetDir = $composer["extra"]["zubzet-asset-dir"] ?? null;
                if (!is_string($assetDir) || '' === $assetDir) continue;
                $package = $composer["name"] ?? null;
                if (!is_string($package) || '' === $package) continue;
                $found[$package] = $assetDir;
            }
            return $found;
        }
    }

?>
