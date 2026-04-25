<?php

    namespace ZubZet\Framework\Resources;

    use League\MimeTypeDetection\FinfoMimeTypeDetector;

    class AssetProxy {

        private array $assetSources = [];

        public function __construct() {
            $this->registerWebRootSource(config("z_framework_root") . "IncludedComponents/assets/");
            $this->registerWebRootSource(config("z_frontend_root"));
        }

        public function registerWebRootSource(string $webRootSource): void {
            if(!is_dir($webRootSource)) {
                throw new \InvalidArgumentException("The provided web root source needs to be a valid directory: $webRootSource");
            }

            // Canonicalize the source so serve() can compare against a stable,
            // symlink-resolved prefix with a trailing directory separator.
            $canonical = realpath($webRootSource);
            if(false === $canonical) {
                throw new \InvalidArgumentException("The provided web root source could not be resolved: $webRootSource");
            }

            $this->assetSources[] = $canonical;
        }

        public function serve(string $assetPath): void {
            $assetPath = ltrim($assetPath, "/");

            foreach($this->assetSources as $source) {
                $fullAssetPath = realpath("$source/$assetPath");

                // Asset not found in this source, try the next one
                if(false === $fullAssetPath) continue;

                // Require an exact directory boundary so sibling paths that merely
                // share a prefix (e.g. "$source" vs "${source}2") are rejected.
                $boundary = $source . DIRECTORY_SEPARATOR;
                if(!str_starts_with($fullAssetPath, $boundary)) {
                    throw new \RuntimeException("Invalid asset path: $fullAssetPath");
                }

                // realpath() resolving a directory doesn't make it servable, skip if it is not
                if(!is_file($fullAssetPath) || !is_readable($fullAssetPath)) continue;

                // Asset found, serve it with the correct MIME type
                $mimeDetector = new FinfoMimeTypeDetector();
                $mimeType = $mimeDetector->detectMimeTypeFromPath($fullAssetPath);

                // If MIME type detection fails, default to a generic binary stream
                if(is_null($mimeType)) $mimeType = "application/octet-stream";

                header("Content-Type: $mimeType");
                readfile($fullAssetPath);
                return;
            }

            // No asset was found, return a 404 response
            http_response_code(404);
            echo "Asset not found: " . e($assetPath);
        }
    }

?>