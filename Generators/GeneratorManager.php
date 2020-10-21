<?php
    class GeneratorManager {
        private $booter;
        public function __construct(&$booter) {
            $this->booter = $booter;
        }

        public function run(Request $req, Response $res, ConsoleContext $ctx) {
            if(method_exists($this, $ctx->getArg(0))) {
                $this->{$ctx->getArg(0)}($req, $res, $ctx);
            } else {
                Console::error("{{light_red}}".$ctx->getArg(0)."{{default}} is not available. Try ", false, !Console::NEW_LINE);
                $methods = array_filter(get_class_methods($this), function($type) {
                    return !in_array($type, ["__construct", "run"]);
                });
                Console::write("{{light_blue}}".implode("{{default}}, {{light_blue}}", $methods)."{{default}}!");
            }
        }

        public function model(Request $req, Response $res, ConsoleContext $ctx) {

        }

        public function view(Request $req, Response $res, ConsoleContext $ctx) {
            
        }

        public function i18n(Request $req, Response $res, ConsoleContext $ctx) {
            
        }

        public function layout(Request $req, Response $res, ConsoleContext $ctx) {
            
        }

        public function controller(Request $req, Response $res, ConsoleContext $ctx) {
            
        }

        public function action(Request $req, Response $res, ConsoleContext $ctx) {
            
        }
    }
?>