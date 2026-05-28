<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar;

    use ZubZet\Framework\ErrorHandling\DebugBar\CanCollect;
    use ZubZet\Framework\ErrorHandling\DebugBar\Collectors\QueryCollector;
    use ZubZet\Framework\ErrorHandling\DebugBar\Collectors\MonologCollector;
    use ZubZet\Framework\ErrorHandling\DebugBar\Collectors\TemplateCollector;

    use DebugBar\StandardDebugBar;
    use Composer\InstalledVersions;
    use DebugBar\JavascriptRenderer;

    class DebugBarBridge {

        use CanCollect;

        private static ?StandardDebugBar $debugBar = null;
        private static ?JavascriptRenderer $renderer = null;

        public static function bootstrap(): void {
            // Only allow the debug bar in dev environment
            if(config("execution_type", default: "prod") !== "test") return;

            // Configure collectors
            $debugBar = new StandardDebugBar();
            $debugBar->addCollector(new QueryCollector);
            $debugBar->addCollector(new TemplateCollector);
            $debugBar->addCollector(new MonologCollector);

            // COnfigure rendering
            $renderer = $debugBar->getJavascriptRenderer();
            $renderer->setHideEmptyTabs(true);

            // Setup the asset proxy
            $vendorPath = InstalledVersions::getInstallPath('php-debugbar/php-debugbar');
            zubzet()->assetProxy->registerWebRootSource("$vendorPath/src/DebugBar/Resources");
            $renderer->setBaseUrl('/_zubzet/asset-proxy');

            self::$debugBar = $debugBar;
            self::$renderer = $renderer;
        }

        public static function isEnabled(): bool {
            return !is_null(self::$debugBar);
        }

        public static function renderHead(): string {
            if(!self::isEnabled()) return "";
            return self::$renderer->renderHead();
        }

        public static function renderBody(): string {
            if(!self::isEnabled()) return "";
            return self::$renderer->render();
        }

        private static function collect(string $collectorName, string $collectionMethod, array $arguments): void {
            // Only collect if the debug bar is enabled
            if(!self::isEnabled()) return;

            // Retrieve the collector and collect the provided data
            $collector = self::$debugBar->getCollector($collectorName);
            call_user_func_array([$collector, $collectionMethod], $arguments);
        }
    }

?>
