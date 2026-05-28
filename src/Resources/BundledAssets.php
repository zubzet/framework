<?php

    namespace ZubZet\Framework\Resources;

    /**
     * Registers the framework's vendored frontend libraries (jQuery, Bootstrap,
     * Font Awesome) with an AssetProxy at the URL paths the framework
     * historically exposed.
     */
    final class BundledAssets {

        public static function register(AssetProxy $proxy): void {
            foreach(self::packages() as $package) {
                $package->mount($proxy);
            }
        }

        /** @return BundledPackage[] */
        private static function packages(): array {
            return [
                new BundledPackage(
                    "components/bootstrap",
                ),
                new BundledPackage(
                    "components/jquery",
                    urlPrefix: "js"
                ),
                new BundledPackage(
                    "components/font-awesome",
                    directoryInPackage: "css",
                    urlPrefix: "css/font-awesome"
                ),
                new BundledPackage(
                    "components/font-awesome",
                    directoryInPackage: "webfonts",
                    urlPrefix: "css/webfonts"
                ),
            ];
        }
    }

?>
