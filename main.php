<?php
    /**
     * Also known as the booter 
     */

    /**
     * First class that is instantiated at a request
     */
    class ZubZet {
        public string $version = "0.11";

        /** @var string $rootDirectory Path to the root */
        public $rootDirectory;

        public $workingDir;

        /** @var string $host Name of the host of this page */
        public $host;

        /** @var string $root Absolute path to the page */
        public $root;

        /** @var string $url URL to reach this page */
        public $url;

        /** @var mysqli $host Database connection object */
        private $conn;

        /** @var string $dbhost Hostname of the machine on that the database lives */
        private $dbhost;

        /** @var string $dbusername Username for the database connection */
        private $dbusername;

        /** @var string $dbpassword Password for the database connection */
        private $dbpassword;

        /** @var string $dbname Name of the database */
        private $dbname;

        /** @var string $default Name of the controller when no specific is selected */
        private $defaultIndex;

        /** @var string[] $urlParts Exploded url */
        public $urlParts;

        /** @var array $settings Stores the ZubZet settings */
        public $settings;

        /** @var Database $z_db Database proxy object  */
        public $z_db;

        /** @var int $showErrors Defines what errors should be shown */
        public $showErrors;

        /** @var string $rootFolder Path to the root folder */
        public $rootFolder;

        /** @var int $maxReroutes Number of reroutes controller can do before abort */
        public $maxReroutes = 10;

        /** @var int $reroutes Number of how many times this request war rerouted */
        public $reroutes = 0;

        /** @var string $zubzet_root Directory where the framework stuff lives */
        public $zubzet_root;

        /** @var string $z_controllers Directory in which the controllers live */
        public $z_controllers = "project/z_controllers/";

        /** @var string $z_models Directory in which the models live in */
        public $z_models = "project/z_models/";

        /** @var string $z_views Directory of the views */
        public $z_views = "project/z_views/";

        /** @var string $config_file Path to the config file */
        public $config_file = "configuration/settings.ini";

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
         * Parses all the options as vars and instantiate the Database and establish the db connection
         */
        function __construct($params = []) {
            chdir(realpath("../"));
            $this->zubzet_root = "include/zubzet/framework/";

            $param_keys = [
                "root" => &$this->zubzet_root, 
                "controllers" => &$this->z_controllers, 
                "models" => &$this->z_models, 
                "views" => &$this->z_views, 
                "config" => &$this->config_file
            ];

            foreach ($param_keys as $key => $param) {
                if (isset($params[$key])) $param = $params[$key];
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

            //Options to attributes
            foreach ($this->settings as $option => $val) {
                $this->$option = $val;
            }

            //Error handling
            $this->updateErrorHandling();

            //Import constants
            require_once $this->zubzet_root . "Constants.php";

            //Parse Post request
            $this->decodePost();

            //Setup the absolute working dir
            $this->workingDir = getcwd()."/";

            //processing the url
            $this->rootFolder = "/".$this->rootDirectory;
            $this->root = $this->host . "/" . $this->rootDirectory;
            $this->url = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "cli");
            $this->urlParts = $this->parseUrl();

            //Database connection
            $this->conn = new mysqli(
                $this->dbhost,
                $this->dbusername,
                $this->dbpassword,
                $this->dbname
            );

            $this->conn->set_charset("utf8mb4_general_ci");

            //Import of the z_db
            require_once $this->zubzet_root.'Database.php';
            $this->z_db = new Database($this->conn, $this);

            //Import the standard controller;
            require_once $this->zubzet_root.'z_controller.php';

            //Import the standard model
            require_once $this->zubzet_root.'z_model.php';

            //RR System
            require_once $this->zubzet_root."RequestResponseHandler.php";
            require_once $this->zubzet_root."Response.php";
            require_once $this->zubzet_root."Request.php";
            $this->req = new Request($this);
            $this->res = new Response($this);

            //User
            require_once $this->zubzet_root.'User.php';
            $this->user = new User($this);
            $this->user->identify();
        }

        /**
         * Updates the error handling state
         * @param Number $state
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
         * Used to parse the url into parts and parameters
         * Format: root/class/method/parameter/parmameter/...
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
         * The Execution of the requested action 
         * @param Array $customUrlParts exmaple: ["panel", "index"]
         */
        public function execute($customUrlParts = null) {
            global $argv;
            if($argv[1] ?? "" == "legacy-update") {
                return $this->executeCommandLine();
            }
            if(isset($customUrlParts)) {
                $this->urlParts = $customUrlParts;
            }
            $this->executePath($this->urlParts);
        }

        public function executeCommandLine() {
            require_once __DIR__."/CommandLine/Core.php";
            $this->cli = new CommandLine_Core($this);
        }

        /**
        * Executes a action for a specified path
        * @param Array $parts exmaple: ["auth", "login"]
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
                } else if (file_exists($this->zubzet_root . "default/controllers/" . $controller . ".php")) {
                    $controllerFile = $this->zubzet_root . "default/controllers/" . $controller . ".php";
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
         * Decodes all data send via post. Decode method can be determined on the prefix of the value
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
            $model .= "Model";
            $path = ($dir == null ? $this->z_models : $dir)."$model.php";
            if (!isset($this->modelCache[$model])) {
                if (file_exists($path)) {
                    require_once $path;
                } else {
                    $path = $this->zubzet_root . "default/models/" . $model . ".php";
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

        /**
         * Returns the path of a view. If the view does not exists, this function will fallback to the framework defaults
         * @param string $document Filename of the view
         * @return string Relative path to the view file
         */
        public function getViewPath($document) {
            if (file_exists($this->z_views.$document)) return $this->z_views.$document;
            if (file_exists($this->zubzet_root."default/views/".$document)) return $this->zubzet_root."default/views/".$document;
            return $this->zubzet_root."default/views/500.php";
        }

        /**
         * Answers this request with a rest
         */
        private function rest($options) {
            require_once $this->zubzet_root.'Rest.php';
            $rest = new Rest($options, $this->urlParts);
            $rest->execute();
        }

        /**
         * Closes the database connection on exit
         */
        function __destruct() {
            if (isset($this->conn)) $this->conn->close();
        }

    }

    //Helper functions

    /**
     * Helper function to get the caller of a function
     * @param int $depth Index of the callstack from back to front
     * @return any The caller
     */
    function getCaller($depth = 1) {
        return debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3)[$depth + 1]['function'];
    }
?>