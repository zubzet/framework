<?php

    namespace ZubZet\Framework\Resources;

    use League\MimeTypeDetection\FinfoMimeTypeDetector;

    class AssetProxy {

        /** @var Mount[] */
        private array $mounts = [];

        public function __construct() {
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

        public function serve(string $assetPath): void {
            $assetPath = ltrim($assetPath, "/");

            foreach($this->mounts as $mount) {
                $file = $mount->resolve($assetPath);
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
