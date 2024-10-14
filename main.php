<?php
    /**
     * Also known as the booter.
     */

    /**
     * First class that is instantiated during a request.
     */
    class z_framework {

        /** @var string $rootDirectory Path to the root directory */
        private $rootDirectory;

        /** @var string $host Name of the host of this page */
        public $host;

        /** @var string $root Absolute path to the page */
        public $root;

        /** @var string $url URL to reach this page */
        public $url;

        /** @var mysqli $conn Database connection object */
        private $conn;

        /** @var string $dbhost Hostname of the machine on which the database resides */
        public $dbhost;

        /** @var string $dbusername Username for the database connection */
        public $dbusername;

        /** @var string $dbpassword Password for the database connection */
        public $dbpassword;

        /** @var string $dbname Name of the database */
        public $dbname;

        /** @var string $defaultIndex Name of the controller when none is specifically selected */
        private $defaultIndex;

        /** @var string[] $urlParts Exploded URL */
        public $urlParts;

        /** @var array $settings Stores the z_framework settings */
        public $settings;

        /** @var z_db $z_db Database proxy object  */
        public $z_db;

        /** @var int $showErrors Defines what errors should be shown */
        public $showErrors;

        /** @var string $rootFolder Path to the root folder */
        public $rootFolder;

        /** @var int $maxReroutes Number of reroutes the controller can perform before aborting */
        public $maxReroutes = 10;

        /** @var int $reroutes Number of times this request was rerouted */
        public $reroutes = 0;

        /** @var string $z_framework_root Directory where the framework files live */
        public $z_framework_root = "z_framework/";

        /** @var string $z_controllers Directory in which the controllers live */
        public $z_controllers = "z_controllers/";

        /** @var string $z_models Directory in which the models live */
        public $z_models = "z_models/";

        /** @var string $z_views Directory of the views */
        public $z_views = "z_views/";

        /** @var string $config_file Path to the config file */
        public $config_file = "z_config/z_settings.ini";

        /** @var array $config An associative array of key-value config parameters  */
        public $config = [];

        /** @var User $user The requesting user */
        public $user;

        /** @var string[] $ControllerStack All visited controllers as an array */
        public $ControllerStack = [];

        /** @var string[] $ActionStack All visited actions as an array */
        public $ActionStack = [];

        /** @var Response $res A reference to an instance of the Response class */
        public $res;

        /** @var Request $req A reference to an instance of the Request class */
        public $req;

        /** @var array[] $action_pattern_replacement Replacement patterns for action names */
        public $action_pattern_replacement = [
            ["-", "_"], 
            [".", "§2E"],
            ["ä", "ae"], 
            ["ö", "oe"], 
            ["ü", "ue"]
        ];
        
        /**
         * Parses all the options as variables, instantiates the z_db, and establishes the db connection.
         */
        function __construct($params = []) {

            chdir(__DIR__."/../");

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
            if (!file_exists($this->config_file)) {
                chdir("./z_framework");
                require_once "./installer.php";
                //Open installer
                exit;
            }

            //Parse ini file with inline comments ignored
            $ini_data = file_get_contents($this->config_file);
            $ini_data = str_replace(";", "-----semicolon-----", $ini_data);
            $ini_data = str_replace("#", "-----hashtag-----", $ini_data);
            $this->config = parse_ini_string($ini_data);
            foreach($this->config as $key => $value) {
                $value = str_replace("-----semicolon-----", ";", $value);
                $value = str_replace("-----hashtag-----", "#", $value);
                $this->config[$key] = $value;
            }
            $this->settings = $this->config;

            //Replace config file with code settings
            foreach($params as $key => $param) {
                if(isset($this->settings[$key])) {
                    $this->settings[$key] = $param;
                }
            }

            //Overwrite using environment vars
            if($this->settings["allow_env_config"] ?? false == true) {
                foreach($this->settings as $key => $setting) {
                    $envName = "CONFIG_".strtoupper($key);
                    if(false !== getenv($envName)) {
                        $this->settings[$key] = getenv($envName);
                    }
                }
            }

            //Options to attributes
            foreach ($this->settings as $option => $val) {
                $this->$option = $val;
            }

            //Error handling
            $this->updateErrorHandling();

            //Import constants
            require_once $this->z_framework_root . "z_constants.php";

            //Import helpers
            include($this->z_framework_root."helpers.php");

            //Parse Post request
            $this->decodePost();

            //processing the url
            $this->rootFolder = "/".$this->rootDirectory;
            $this->root = $this->host . "/" . $this->rootDirectory;
            $this->url = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "cli");
            $this->urlParts = $this->parseUrl();

            // Import the standard controller;
            require_once $this->z_framework_root.'z_controller.php';

            // Import the standard model
            require_once $this->z_framework_root.'z_model.php';

            // RR System
            require_once $this->z_framework_root."z_requestResponseHandler.php";
            require_once $this->z_framework_root."z_response.php";
            require_once $this->z_framework_root."z_request.php";

            $this->req = new Request($this);
            $this->res = new Response($this);

            // Import of the z_db
            require_once $this->z_framework_root.'z_db.php';
            $this->z_db = new z_db($this);

            // User
            require_once $this->z_framework_root.'z_user.php';
            $this->user = new User($this);
            $this->user->identify();
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

        /**
         * Used to parse the URL into parts and parameters
         * Format: root/class/method/parameter/parameter/...
         */
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

        /** 
         * Executes the requested action
         * @param array|null $customUrlParts Example: ["panel", "index"]
         */
        public function execute($customUrlParts = null) {
            global $argv;
            if(isset($argv)) {
                if(($argv[1] ?? null) == "run") {
                    $customUrlParts = array_slice($argv, 2);
                }
            }

            //Be able to force custom 
            if(isset($customUrlParts)) {
                $this->urlParts = $customUrlParts;
            }
            $this->executePath($this->urlParts);
        }

        /**
        * Executes an action for a specified path
        * @param array $parts Example: ["auth", "login"]
        */
        public function executePath($parts) {
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
            
            $method = urldecode($method);
            $controller = urldecode($controller);

            foreach ($this->action_pattern_replacement as $apr) {
                $method = str_replace($apr[0], $apr[1], $method);
                $controller = str_replace($apr[0], $apr[1], $controller);
            }

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
            } catch (Exception $e) {
                return $this->executePath(["error", "500"]);
            }

            //Update values
            $this->ControllerStack[] = $controller;
            $this->ActionStack[] = $method;
            
            try {
                $CTRL_obj = new $controller($this->req, $this->res);
                if (method_exists($controller, $method)) {
                    return $CTRL_obj->{$method}($this->req, $this->res);
                } else {
                    //Checks if the fallback method exists before rerouting to the 404 page
                    $method = "action_fallback";
                    if (method_exists($controller, $method)) {
                        $this->ActionStack[] = $method;
                        return $CTRL_obj->{$method}($this->req, $this->res);
                    } else {
                        return $this->executePath(["error", "404"]);
                    }
                }
            } catch(Exception $e) {
                if ($this->showErrors != 0) {
                    throw $e;
                } else {
                    return $this->executePath(["error", "500"]);
                }
            }
            
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

        /** @var z_Model[] Stores all already used models for this request */
        private $modelCache = [];

        /**
         * Returns a model
         * @param string $model Name of the model
         * @param string $dir Set this when the model is stored in a specific directory
         * @return z_model The model
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
                    $path = $this->z_framework_root . "default/models/" . $model . ".php";
                    if (file_exists($path)) {
                        require_once $path;
                    } else {
                        throw new Exception("Model: $model does not exist!");
                    }
                }
                
                // Only use the last part of the model name as the class Name
                $model = explode(DIRECTORY_SEPARATOR, $model);
                $model = array_pop($model);

                $this->modelCache[$model] = new $model($this->z_db, $this);
            }
            return $this->modelCache[$model];
        }

        /**
         * Returns the path of a view. If the view does not exist, this function will fall back to the framework defaults.
         * @param string $document Filename of the views
         * @return string Relative path to the view file
         */
        public function getViewPath(...$documents) {
            foreach($documents as $document) {
                if(substr($document, -4, 4) != ".php") {
                    $document .= ".php";
                }
                if (file_exists($this->z_views.$document)) {
                    return $this->z_views.$document;
                }
                if (file_exists($this->z_framework_root."default/views/$document")) {
                    return $this->z_framework_root."default/views/$document";
                }
            }
            return $this->z_framework_root."default/views/500.php";
        }

        /**
         * Answers this request with a REST
         */
        private function rest($options) {
            require_once $this->z_framework_root.'z_rest.php';
            $rest = new Rest($options, $this->urlParts);
            $rest->execute();
        }

    }

?>
