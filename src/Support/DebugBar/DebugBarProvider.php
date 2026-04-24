<?php

    namespace ZubZet\Framework\Support\DebugBar;

    use Composer\InstalledVersions;
    use DebugBar\Bridge\MonologCollector;
    use DebugBar\JavascriptRenderer;
    use DebugBar\StandardDebugBar;
    use Monolog\Formatter\LineFormatter;
    use ZubZet\Framework\Support\DebugBar\Collectors\QueryCollector;
    use ZubZet\Framework\Support\DebugBar\Collectors\RenderCollector;

    class DebugBarProvider {

        private static ?StandardDebugBar $debugBar = null;
        private static ?JavascriptRenderer $debugBarRenderer = null;

        public function __construct() {
            if(config("execution_type", default: "prod") !== "test") return;
            $debugBar = new StandardDebugBar();
            $debugBarRenderer = $debugBar->getJavascriptRenderer();

            // Add collectors
            $debugBar->addCollector(new QueryCollector());
            $debugBar->addCollector(new RenderCollector());
            $debugBar->addCollector($this->getMonologCollector());

            // Hide tabs that are replaced by custom collectors
            $debugBarRenderer->ignoreCollector("messages");
            $debugBarRenderer->ignoreCollector("time");
            $debugBarRenderer->ignoreCollector("exceptions");

            // Register the DebugBar assets with the asset proxy
            $baseFolder = InstalledVersions::getInstallPath('php-debugbar/php-debugbar') . "/src/DebugBar/Resources";
            zubzet()->assetProxy->registerWebRootSource($baseFolder);
            $debugBarRenderer->setBaseUrl('/_zubzet/asset-proxy');

            self::$debugBar = $debugBar;
            self::$debugBarRenderer = $debugBarRenderer;
        }

        // Renders the DebugBar head section (CSS and JS includes)
        public static function renderHead(): string {
            return self::$debugBarRenderer?->renderHead() ?? "";
        }

        // Renders the DebugBar body section (the actual bar and widgets)
        public static function renderBody(): string {
            return self::$debugBarRenderer?->render() ?? "";
        }

        // Provides access to the DebugBar instance for adding data from other parts of the application
        public static function getDebugBar(): ?StandardDebugBar {
            return self::$debugBar;
        }

        public static function getMonologCollector(): MonologCollector {
            $monologCollector = new MonologCollector();
            $monologCollector->setFormatter(new LineFormatter(null, 'H:i:s'));
            return $monologCollector;
        }

        public static function queryCollector(): ?QueryCollector {
            $debugBar = self::getDebugBar();

           return $debugBar?->getCollector("queries");
        }

        public static function renderCollector(): ?RenderCollector {
            $debugBar = self::getDebugBar();

           return $debugBar?->getCollector("renders");
        }

        public static function monologCollector(): ?MonologCollector {
            $debugBar = self::getDebugBar();

           return $debugBar?->getCollector("monolog");
        }

    }

?>