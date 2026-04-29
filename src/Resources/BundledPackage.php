<?php

    namespace ZubZet\Framework\Resources;

    use Composer\InstalledVersions;

    /**
     * One Composer-installed frontend library that should be exposed via the
     * AssetProxy. Resolves its on-disk path through Composer at mount time.
     *
     * Example:
     *   new BundledPackage(
     *       package: "components/font-awesome",         // The Composer package name
     *       directoryInPackage: "webfonts",             // Directory inside the package
     *       urlPrefix: "css/webfonts",                  // URL prefix it lives at
     *   );
     *
     *  => requests for "/_zubzet/asset-proxy/css/webfonts/fa-solid-900.woff2"
     *     serves "vendor/components/font-awesome/webfonts/fa-solid-900.woff2".
     *
     * Both directoryInPackage and urlPrefix default to empty:
     *  - omit directoryInPackage to expose the package root
     *  - omit urlPrefix to mount at the asset-proxy root
     */
    final class BundledPackage {

        public string $package;
        public string $directoryInPackage = "";
        public string $urlPrefix = "";

        public function __construct($package, $directoryInPackage = "", $urlPrefix = "") {
            $this->package = $package;
            $this->urlPrefix = $urlPrefix;
            $this->directoryInPackage = empty($directoryInPackage) ? "" : "/$directoryInPackage";
        }

        public function mount(AssetProxy $proxy): void {
            try {
                $base = InstalledVersions::getInstallPath($this->package);
            } catch(\OutOfBoundsException) {
                return;
            }

            if(is_null($base)) {
                throw new \RuntimeException("Package is not installed: " . $this->package);
            }

            $proxy->registerWebRootSource(
                rtrim($base, "/\\") . $this->directoryInPackage,
                $this->urlPrefix,
            );
        }
    }

?>
