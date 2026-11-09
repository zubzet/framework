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
         * This does not immediately check the filesystem; a missing directory only means requests for
         * its assets end in a 404. The sourceRoot should be an absolute path. The first registered
         * mount that has the file wins.
         * @param string $urlPrefix Optional URL path prefix the mount is scoped to; empty serves from the URL root.
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
