<?php

    namespace ZubZet\Framework\Resources;

    use ZubZet\Framework\ZubZet;
    use League\MimeTypeDetection\FinfoMimeTypeDetector;

    class AssetProxy {

        private array $assetSources = [];

        public function __construct() {
            $this->registerWebRootSource(config("z_framework_root") . "IncludedComponents/assets/");
        }

        public function registerWebRootSource(string $webRootSource): void {
            $webRootSource = rtrim($webRootSource, "/");

            if(!is_dir($webRootSource)) {
                throw new \InvalidArgumentException("The provided web root source is not a valid directory: " . $webRootSource);
            }

            $this->assetSources[] = $webRootSource;
        }

        public function serve(string $assetPath): void {
            $assetPath = ltrim($assetPath, "/");

            foreach($this->assetSources as $source) {
                $fullAssetPath = realpath("$source/$assetPath");
                if(false === $fullAssetPath) {
                    // Asset not found in this source, try the next one
                    continue;
                }

                if(!str_starts_with($fullAssetPath, $source)) {
                    throw new \RuntimeException("Invalid asset path: $fullAssetPath");
                }

                $mimeDetector = new FinfoMimeTypeDetector();
                $mimeType = $mimeDetector->detectMimeTypeFromPath($fullAssetPath);

                header("Content-Type: $mimeType");
                readfile($fullAssetPath);
                return;
            }

            // Not asset was found, return a 404 response
            http_response_code(404);
            echo "Asset not found: " . e($assetPath);
        }
    }

?>