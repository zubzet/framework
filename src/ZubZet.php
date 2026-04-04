<?php

    namespace ZubZet\Framework;

    use ZubZet\Framework\Routing\Route;
    use ZubZet\Framework\Core\Constants;
    use ZubZet\Framework\Support\Helpers;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\Message\Response;
    use ZubZet\Framework\Console\Application;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Bootstrap\Configuration;
    use ZubZet\Framework\Support\GlobalReferences;

    use Slim\App;
    use Slim\Factory\AppFactory;
    use Slim\Psr7\Response as HttpResponse;
    use Slim\Exception\HttpNotFoundException;
    use Slim\Factory\ServerRequestCreatorFactory;

    class ZubZet {
        /** @var Connection $z_db Database proxy object  */
        public $z_db;

        public Configuration $configuration;

        /** @var \User $user The requesting user */
        public $user;

        /** @var \Response $res A reference to an instance of the Response class */
        public $res;

        /** @var \Request $req A reference to an instance of the Request class */
        public $req;

        /** @var App $slimApplication The instance of the Slim application */
        public $slimApplication;

        /**
         * @internal
         * @var ZubZet The instance of the framework
         */
        public static ?ZubZet $instance = null;

        /**
         * Parses all the options as variables, instantiates the z_db, and establishes the db connection.
         */
        function __construct(array $params = []) {
            self::$instance = $this;
            new GlobalReferences;

            $this->configuration = new Configuration(
                __DIR__ . DIRECTORY_SEPARATOR,
                $params,
            );

            //Error handling
            $this->updateErrorHandling();

            // Static Imports
            new Constants;
            new Helpers;

            //Parse Post request
            $this->decodePost();

            //processing the url
            $this->rootFolder = "/".$this->rootDirectory;
            $this->root = $this->host . "/" . $this->rootDirectory;
            $this->url = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "cli");
            $this->urlParts = $this->parseUrl();

            // Message System
            $this->req = new Request($this);
            $this->res = new Response($this);

            // Import of the database connection
            $this->z_db = new Connection($this);

            // User
            $this->user = new User();
        }

        public function __set(string $name, mixed $value): void {
            $this->configuration->{$name} = $value;
        }

        public function __get(string $name): mixed {
            return $this->configuration->{$name};
        }

        /**
         * Updates the error handling state
         * @param int|null $state
         */
        public function updateErrorHandling($state = null) {
            //State or attribute check
            $this->showErrors = ($state != null ? $state : $this->showErrors);
            //custom error handler or standard
            if ($this->showErrors > 1) {
                //Custom error function (even triggers for warnings)
                set_error_handler(function($severity, $message, $file, $line) {
                    if (error_reporting() & $severity) {
                        throw new \ErrorException($message, 0, $severity, $file, $line);
                    }
                });
            } else {
                //Standard Exception Handling on / off
                ini_set('display_errors', $this->showErrors);
                ini_set('display_startup_errors', $this->showErrors);
                error_reporting($this->showErrors == 1 ? E_ALL : 0);
            }
        }

        /**
         * Used to parse the URL into parts and parameters
         * Format: root/class/method/parameter/parameter/...
         */
        private function parseUrl() {
            $path = parse_url($this->url, PHP_URL_PATH) ?: "";
            $path = trim($path, '/');
            $this->rootDirectory = trim($this->rootDirectory, '/');

            $urlParts = $path !== "" ? explode("/", $path) : [];

            $this->rootDirectory = $this->rootDirectory !== "" ? explode("/", $this->rootDirectory) : [];
            for ($i = 0; $i < count($this->rootDirectory); $i++) array_shift($urlParts);

            return $urlParts;
        }

        /*
         * Handles the incoming request
         * Firstly, it tryes to load routes from Slim, then it falls back to the ZubZet framework.
         */
        public function execute($customUrlParts = null) {
            // Create a new Slim App instance
            $this->slimApplication = AppFactory::create();

            // Initialize the Route class with the Slim app and this framework instance
            Route::init($this);

            // Uses to register all routes for Slim
            $this->loadRoutes();

            // Execute the Slim Route
            try  {
                $this->slimApplication->run();
            } catch (HttpNotFoundException $e) {
                // Fallback to ZubZet
                $this->handleRequest($customUrlParts);

                // Return a PSR response to stop further processing
                return new HttpResponse();
            }
        }

        /*
        * Loads all routes in "z_routes"
        */
        private function loadRoutes() {
            // get all php files in the z_routes directory
            $routeFiles = glob($this->routes . "/*.php");

            // require them to register the routes
            foreach ($routeFiles as $file) {
                require_once $file;
            }
        }

        /** 
         * Executes the requested action
         * @param array|null $customUrlParts Example: ["panel", "index"]
         */
        private function handleRequest($customUrlParts = null) {
            if($this->req->isCli()) {
                $console = Application::bootstrap($this);
                $console->run();
                return;
            }

            //Be able to force custom 
            if(isset($customUrlParts)) {
                $this->urlParts = $customUrlParts;
            }

            // it should perform the middleware groups matching the prefix
            Route::performStoredGroupsMatchingPrefix($this->urlParts, function() {
                return $this->executePath($this->urlParts);
            });
        }


        public function executeControllerAction($controller, $action, array $params = []) {
            $this->req->urlParameters = $params;

            $controller = urldecode($controller);
            $method = urldecode($action);

            $actionPatternReplacement = [
                ["-", "_"],
                [".", "§2E"],
                ["ä", "ae"],
                ["ö", "oe"],
                ["ü", "ue"]
            ];

            foreach($actionPatternReplacement as $apr) {
                $method = str_replace($apr[0], $apr[1], $method);
                $controller = str_replace($apr[0], $apr[1], $controller);
            }

            try {
                $controllerFile = null;
                if (file_exists($this->z_controllers . $controller . ".php")) {
                    $controllerFile = $this->z_controllers . $controller . ".php";
                } else if (file_exists($this->z_framework_root . "IncludedComponents/controllers/" . $controller . ".php")) {
                    $controllerFile = $this->z_framework_root . "IncludedComponents/controllers/" . $controller . ".php";
                }

                if ($controllerFile !== null) {
                    include_once($controllerFile);
                } else {
                    return $this->executePath(["error", "404"]);
                }
            } catch (\Exception $e) {
                return $this->executePath(["error", "500"]);
            }

            try {
                $CTRL_obj = new $controller($this->req, $this->res);
                if (method_exists($controller, $method)) {
                    return $CTRL_obj->{$method}($this->req, $this->res);
                } else {
                    //Checks if the fallback method exists before rerouting to the 404 page
                    $method = "action_fallback";
                    if (method_exists($controller, $method)) {
                        return $CTRL_obj->{$method}($this->req, $this->res);
                    } else {
                        return $this->executePath(["error", "404"]);
                    }
                }
            } catch(\Exception $e) {
                if ($this->showErrors != 0) {
                    throw $e;
                } else {
                    return $this->executePath(["error", "500"]);
                }
            }
        }

        /**
         * Tries to reroute the request using Slim first, if the route does not exist, it falls back to the ZubZet framework.
         *
         * @param mixed $parts The parts of the new route. Example: ["auth", "login"]
         * @return void
         */
        public function reroute($parts) {
            try {
                $joinedParts = implode("/", $parts);

                $request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals();

                $request = $request->withUri(
                    $request->getUri()->withPath(
                        "/$joinedParts"
                    )
                );

                $this->slimApplication->run($request);
            } catch(HttpNotFoundException $e) {
                // Fallback to ZubZet
                $this->executePath($parts);
            }
        }

        /** @var int $maxReroutes Number of reroutes the controller can perform before aborting */
        public $maxReroutes = 10;

        /** @var int $reroutes Number of times this request was rerouted */
        public $reroutes = 0;

        /**
        * Executes an action for a specified path
        * @param array $parts Example: ["auth", "login"]
        */
        public function executePath($parts) {
            $this->urlParts = $parts;

            $this->reroutes++;
            if ($this->reroutes > $this->maxReroutes) die("Error: Too many reroutes. Please contact the webmaster.");

            if (isset($parts[0])) {
                $controller = ucfirst($parts[0]) . 'Controller';
            } else {
                $controller = $this->defaultIndex;
            }

            if (isset($parts[1])) {
                $method = "action_" . strtolower($parts[1]);
            } else {
                $method = "action_index";
            }

            $this->executeControllerAction($controller, $method);
        }

        /**
         * Decodes all data sent via POST. Decoding method can be determined by the prefix of the value.
         */
        private function decodePost() {
            array_walk_recursive($_POST, function(&$item) {
                if(substr($item, 0, 10) == "<#decb64#>") {
                    $item = substr($item, 10);
                    $item = base64_decode($item);
                }
                if(substr($item, 0, 10) == "<#decURI#>") {
                    $item = substr($item, 10);
                    $item = rawurldecode($item);
                }
            });
        }

        /** @var \z_model[] Stores all already used models for this request */
        private $modelCache = [];

        /**
         * Returns a model
         * @param string $model Name of the model
         * @param string $dir Set this when the model is stored in a specific directory
         * @return \z_model The model
         */
        public function getModel($model, $dir = null) {
            $modelParts = explode(".", $model);

            if(count($modelParts) > 1) {
                $lastPart = array_pop($modelParts);
                $modelParts = array_map("strtolower", $modelParts);
                $model = implode(DIRECTORY_SEPARATOR, $modelParts) . DIRECTORY_SEPARATOR . $lastPart;
            }

            $model .= "Model";
            $path = ($dir == null ? $this->z_models : $dir)."$model.php";

            if (!isset($this->modelCache[$model])) {
                if (file_exists($path)) {
                    require_once $path;
                } else {
                    $path = $this->z_framework_root . "IncludedComponents/models/" . $model . ".php";
                    if (file_exists($path)) {
                        require_once $path;
                    } else {
                        throw new \Exception("Model: $model does not exist!");
                    }
                }
                
                // Only use the last part of the model name as the class Name
                $model = explode(DIRECTORY_SEPARATOR, $model);
                $model = array_pop($model);

                $this->modelCache[$model] = new $model($this->z_db, $this);
            }
            return $this->modelCache[$model];
        }

    }

?>
