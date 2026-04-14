<?php

    namespace ZubZet\Framework\ErrorHandling;

    use Whoops\Run;
    use Whoops\Handler\PlainTextHandler;
    use Whoops\Handler\PrettyPageHandler;

    class DebugHelper {

        private ?Run $whoops = null;

        public function __construct() {
            if(config("execution_type", default: "prod") !== "test") return;

            $this->registerWhoops();
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
    }

?>