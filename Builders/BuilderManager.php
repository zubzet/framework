<?php
    class BuilderManager {
        private $booter;
        public function __construct(z_framework &$booter) {
            $this->booter = $booter;
        }

        public function run(Request $req, Response $res, ConsoleContext $ctx) {
            if(method_exists($this, $ctx->getArg(0))) {
                require_once __DIR__."/TemplateBuilder.php";
                $this->{$ctx->getArg(0)}($req, $res, $ctx);
                Console::write("Component generated!");
            } else {
                Console::error("{{light_red}}".$ctx->getArg(0)."{{default}} is not available. Try ", false, !Console::NEW_LINE);
                $methods = array_filter(get_class_methods($this), function($type) {
                    return !in_array($type, ["__construct", "run", "getName", "makeComponent"]);
                });
                Console::write("{{light_blue}}".implode("{{default}}, {{light_blue}}", $methods)."{{default}}!");
            }
        }

        private function getName(ConsoleContext $ctx) {
            $name = $ctx->getArg(1, "");
            if(empty($name)) {
                Console::error("Please specify a name for the component");
            }
            return str_replace(["-", " ", "."], "_", $name);
        }

        private function makeComponent(ConsoleContext $ctx, string $template, string $path, string $suffix = "") {
            $name = $this->getName($ctx);
            $template = new TemplateBuilder(
                $this->booter->workingDir, 
                $template
            );
            $template->setName($name);
            $template->saveTo($path, $suffix);
        }

        public function model(Request $req, Response $res, ConsoleContext $ctx) {
            $this->makeComponent(
                $ctx, 
                "Model.cmpnt", 
                $this->booter->z_models,
                "Model"
            );
        }

        public function view(Request $req, Response $res, ConsoleContext $ctx) {
            $this->makeComponent(
                $ctx, 
                "View.cmpnt", 
                $this->booter->z_views,
            );
        }

        public function i18n(Request $req, Response $res, ConsoleContext $ctx) {
            
        }

        public function layout(Request $req, Response $res, ConsoleContext $ctx) {
            
        }

        public function controller(Request $req, Response $res, ConsoleContext $ctx) {
            $this->makeComponent(
                $ctx, 
                "Controller.cmpnt", 
                $this->booter->z_controllers,
                "Controller"
            );
        }

        public function action(Request $req, Response $res, ConsoleContext $ctx) {
            $name = ucfirst($this->getName($ctx))."Controller";
            $controller = $this->booter->workingDir.$this->booter->z_controllers.$name.".php";
            if(!file_exists($controller)) {
                Console::error("The controller {{light_blue}}$name {{default}}does not exist.", true);
            }

            $action = "action_".$ctx->getArg(2);
            $controllerContent = file_get_contents($controller);
            if(FALSE !== strpos($controllerContent, "function $action")) {
                Console::error("The action {{light_blue}}$action {{default}}already exist.", true);
            }

            $actionTemplate = file_get_contents(__DIR__."/templates/Action.cmpnt");
            $actionTemplate = str_replace("%%name%%", $action, $actionTemplate);

            $controllerContent = explode("\n", $controllerContent);
            $classLine = 0;
            foreach($controllerContent as $i => $line) {
                if(strpos($line, "Controller")) {
                    $classLine = $i+1;
                    break;
                }
            }
            if(!isset($controllerContent[$i])) $controllerContent[$i] = "";
            $controllerContent[$i] .= "\n$actionTemplate";
            $controllerContent = implode("\n", $controllerContent);

            file_put_contents($controller, $controllerContent);
        }

        public function migration(Request $req, Response $res, ConsoleContext $ctx) {
            
        }
    }
?>