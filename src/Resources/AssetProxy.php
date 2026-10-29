<?php

    namespace ZubZet\Framework\Resources;

    use League\MimeTypeDetection\FinfoMimeTypeDetector;
    use ZubZet\Framework\Registry\Registry;

    class AssetProxy {

        /** @var Mount[] */
        private array $mounts = [];

        public function __construct() {
            // Mount order follows the global precedence: modules first (in
            // module order), framework layers after. The application's own
            // webroot/ is served by the web server before PHP ever runs, so
            // userspace keeps the top of the chain without a mount here.
            foreach(Registry::moduleRoots("assets") as $moduleWebRoot) {
                $this->registerWebRootSource($moduleWebRoot);
            }

            $this->registerWebRootSource(config("z_framework_root") . "IncludedComponents/assets/");
            $this->registerWebRootSource(config("z_frontend_root"));
            BundledAssets::register($this);
        }

        /**
         * This function registers a directory on disk as a source of assets to be served by the proxy.
         * This does not immediately check the filesystem and will only fail once a request is made for
         * an asset that belongs to this mount. The sourceRoot should be an absolute path.
         */
        public function registerWebRootSource(string $sourceRoot, string $urlPrefix = ''): void {
            $this->mounts[] = new Mount($sourceRoot, $urlPrefix);
        }

        /** Extensions that are never served, whatever mount they resolve in. */
        private const DENIED_EXTENSIONS = ["php", "phtml", "ini"];

        public function serve(string $assetPath): void {
            $assetPath = ltrim($assetPath, "/");

            $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
            if(in_array($extension, self::DENIED_EXTENSIONS, true)) {
                http_response_code(404);
                echo "Asset not found: " . e($assetPath);
                return;
            }

            foreach($this->mounts as $mount) {
                try {
                    $file = $mount->resolve($assetPath);
                } catch(\RuntimeException $e) {
                    // Traversal escapes resolve to a 404, not an error page.
                    break;
                }
                if(is_null($file)) continue;

                // Only a link carrying the configured assetVersion is busted by changing it, "dev" links carry time() instead
                $version = config("assetVersion", default: "");
                $isVersioned = !empty($version) && "dev" !== $version && $version === (request()->input->GET["v"] ?? "");

                // Cache control: versioned assets are immutable, unversioned assets are not cached
                header("Cache-Control: " . ($isVersioned ? "public, max-age=31536000, immutable" : "no-cache"));

                $fingerprint = sprintf("%x-%x", filemtime($file), filesize($file));
                header("ETag: \"$fingerprint\"");

                // Matches our entity tag, also weak (W/) or with the -gzip/-br suffix Apache's compression appends
                if(preg_match("#\"$fingerprint(-gzip|-br)?\"#", request()->input->SERVER["HTTP_IF_NONE_MATCH"] ?? "")) {
                    http_response_code(304);
                    return;
                }

                $mime = (new FinfoMimeTypeDetector())->detectMimeTypeFromPath($file) ?? "application/octet-stream";
                header("Content-Type: $mime");
                readfile($file);
                return;
            }

            http_response_code(404);
            echo "Asset not found: " . e($assetPath);
        }
    }

?>
