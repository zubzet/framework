<?php
    class z_framework {

        public $settings;
        private $rootDirectory;
        private $host;
        private $root;
        private $url;
        public $urlParts;
        private $document;
        private $conn;
        private $dbhost;
        private $dbusername;
        private $dbpassword;
        private $dbname;
        public $z_db;
        private $defaultIndex;
        public $showErrors;
        public $rootFolder;
        public $maxReroutes = 10;
        public $reroutes = 0;
        public $z_framework_root = "z_framework/";
        public $z_controllers = "z_controllers/";
        public $z_models = "z_models/";
        public $z_views = "z_views/";
        public $config_file = "z_config/z_settings.ini";

        public $user;

        //Parse all the options as vars and instantiate the z_db
        //and establish the db connection
        function __construct($params = []) {

            $param_keys = [
                "root" => &$this->z_framework_root, 
                "controllers" => &$this->z_controllers, 
                "models" => &$this->z_models, 
                "views" => &$this->z_views, 
                "config" => &$this->config_file
            ];

            foreach ($param_keys as $key => $param) {
                if (isset($params[$key])) $param = $params[$key];
            }

            //Config file
            if (!file_exists($this->config_file)) die("Config file could not be found.");
            $this->config = parse_ini_file($this->config_file);
            $this->settings = $this->config;

            //Options to attributes
            foreach ($this->settings as $option => $val) {
                $this->$option = $val;
            }

            //Error handling
            $this->updateErrorHandling();

            //Import constants
            require_once $this->z_framework_root . "z_constants.php";

            //Parse Post request
            $this->decodePost();

            //processing the url
            $this->rootFolder = "/".$this->rootDirectory;
            $this->root = $this->host . "/" . $this->rootDirectory;
            $this->url = (empty($options["url"]) ? $_SERVER['REQUEST_URI'] : $options["url"]);
            $this->urlParts = $this->parseUrl();

            //Database connection
            $this->conn = new mysqli(
                $this->dbhost,
                $this->dbusername,
                $this->dbpassword,
                $this->dbname
            );

            $this->conn->set_charset("utf8");

            //Import of the z_db
            require_once $this->z_framework_root.'z_db.php';
            $this->z_db = new z_db($this->conn, $this);

            //Import the standard controller;
            require_once $this->z_framework_root.'z_controller.php';

            //Import the standard model
            require_once $this->z_framework_root.'z_model.php';

            //RR System
            require_once $this->z_framework_root."z_requestResponseHandler.php";
            require_once $this->z_framework_root."z_response.php";
            require_once $this->z_framework_root."z_request.php";

            //User
            require_once $this->z_framework_root.'z_user.php';
            $this->user = new User($this);
            $this->user->identify();
        }

        public function updateErrorHandling($state = null) {
            //State or attribute check
            $this->showErrors = ($state != null ? $state : $this->showErrors);

            //custom error handler or standard
            if ($this->showErrors > 1) {

                //Custom error function (even triggers for warnings)
                set_error_handler(function($severity, $message, $file, $line) {
                    if (error_reporting() & $severity) {
                        throw new ErrorException($message, 0, $severity, $file, $line);
                    }
                });
                
            } else {
                //Standard Exception Handling on / off
                ini_set('display_errors', $this->showErrors);
                ini_set('display_startup_errors', $this->showErrors);
                error_reporting($this->showErrors == 1 ? E_ALL : 0);

            }
        }

        //Used to parse the url into parts and parameters
        //Format: root/class/method/parameter/parmameter/...
        private function parseUrl() {
            $path = parse_url($this->url, PHP_URL_PATH);
            $path = ltrim($path, '/');
            $path = rtrim($path, '/');
            $this->rootDirectory = ltrim($this->rootDirectory, '/');
            $this->rootDirectory = rtrim($this->rootDirectory, '/');
            
            $urlParts = $path !== "" ? explode("/", $path) : [];

            $this->rootDirectory = $this->rootDirectory !== "" ? explode("/", $this->rootDirectory) : [];
            for ($i = 0; $i < count($this->rootDirectory); $i++) array_shift($urlParts);

            return $urlParts;
        }

        //The Execution of the requested action
        public function execute() {
            $this->executePath($this->urlParts);
        }

        /**
        * Executes a action for a specified path
        * @param Array $parts exmaple: ["auth", "login"]
        */
        public function executePath($parts) {
            $this->reroutes++;
            if ($this->reroutes > $this->maxReroutes) die("Error: Too many reroutes. Please contact the webmaster.");

            if (isset($parts[1])) {
                $method = "action_" . strtolower($parts[1]);
            } else {
                $method = "action_index";
            }

            if (isset($parts[0])) {
                $controller = ucfirst($parts[0]) . 'Controller';
            } else {
                $controller = $this->defaultIndex;
            }

            $method = str_replace("-", "_", $method);
            $controller = str_replace("-", "_", $controller);
            
            try {
                $controllerFile = null;
                if (file_exists($this->z_controllers . $controller . ".php")) {
                    $controllerFile = $this->z_controllers . $controller . ".php";
                } else if (file_exists($this->z_framework_root . "default/controllers/" . $controller . ".php")) {
                    $controllerFile = $this->z_framework_root . "default/controllers/" . $controller . ".php";
                }

                if ($controllerFile !== null) {
                    include_once($controllerFile);
                } else {
                    return $this->executePath(["error", "404"]);
                }
            } catch (Exeption $e) {
                return $this->executePath(["error", "404"]);
            }

            if (method_exists($controller, $method)) {
                $CTRL_obj = new $controller();
                return $CTRL_obj->{$method}(new Request($this), new Response($this));
            } else {
                return $this->executePath(["error", "404"]);
            }
        }

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

        private $modelCache = [];
        public function getModel($model, $dir = null) {
            $model .= "Model";
            $path = ($dir == null ? $this->z_models : $dir)."$model.php";
            if (!isset($this->modelCache[$model])) {
                if (file_exists($path)) {
                    require_once $path;
                } else {
                    $path = $this->z_framework_root . "default/models/" . $model . ".php";
                    if (file_exists($path)) {
                        require_once $path;
                    } else {
                        throw new Exception("Model: $model does not exist!");
                    }
                }                
                $this->modelCache[$model] = new $model($this->z_db, $this);
            }
            return $this->modelCache[$model];
        }

        public function getViewPath($document) {
            if (file_exists($this->z_views.$document)) return $this->z_views.$document;
            if (file_exists($this->z_framework_root."default/views/".$document)) return $this->z_framework_root."default/views/".$document;
            return false;
        }

        private function rest($options) {
            require_once $this->z_framework_root.'z_rest.php';
            $rest = new Rest($options, $this->urlParts);
            $rest->execute();
        }

        //close the db connection on exit
        function __destruct() {
            if (isset($this->conn)) $this->conn->close();
        }

    }

    //HELPER Functions

    function getCaller($depth = 1) {
        return debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3)[$depth + 1]['function'];
    }
?>