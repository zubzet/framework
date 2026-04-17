<?php

    namespace ZubZet\Framework\ErrorHandling;

use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Whoops\Run;
    use Whoops\Handler\PlainTextHandler;
    use Whoops\Handler\PrettyPageHandler;

    class DebugHelper {

        private static ?DebugHelper $instance = null;

        private ?Run $whoops = null;

        private ?StandardDebugBar $debugBar;
        private ?JavascriptRenderer $debugBarRenderer;

        public static function renderHead() {
            return self::$instance->debugBarRenderer?->renderHead() ?? "";
        }

        public static function renderBody() {
            return self::$instance->debugBarRenderer?->render() ?? "";
        }

        public function __construct() {
            if(config("execution_type", default: "prod") !== "test") return;

            self::$instance = $this;

            $this->registerWhoops();
            $this->registerDebugBar();
        }

        public function registerWhoops(): void {
            $this->whoops = new Run;

            // Handle different handlers for CLI vs web requests.
            match(request()->isCli()) {
                true => $this->whoops->pushHandler(new PlainTextHandler),
                false => $this->whoops->pushHandler(new PrettyPageHandler),
            };

            $this->whoops->register();
        }

        public function registerDebugBar(): void {
            if(request()->isCli()) return;

            $this->debugBar = new StandardDebugBar;
            $this->debugBarRenderer = $this->debugBar->getJavascriptRenderer();

            $this->debugBarRenderer->setBaseUrl("/core/debug-bar");
        }

    }

?>