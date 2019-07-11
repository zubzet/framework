<?php 
    /**
     * Route handling system documentation:
     * Every action takes two parameters.
     * Request => used to get incoming and session stuff
     * Response => used to handle outgoing stuff
     */

    class Request {

        private $booter;

        public function __construct($booter) {
            $this->booter = $booter;
        }

        public function getZModels() {
            return $this->booter->z_models;
        }

        public function getZRoot() {
            return $this->booter->z_framework_root; 
        }

        /**
         * Gets a get parameter
         * @param string $key of the parameter
         * @param string $default Default value
         * @return array|string Content of the get value
         */
        public function getGet($key, $default = null) {
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
            return $default;
        }

        /**
         * Gets a post parameter
         * @param string $key of the parameter
         * @param string $default Default value
         * @return array|string Content of the post value
         */
        public function getPost($key, $default = null) {
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
            return $default;
        }

        /**
         * Gets a cookie
         * @param String $key of the parameter
         * @param String $default Default value
         * @return any Content of the Cookie
         */
        public function getCookie($name, $default = null) {
            if (isset($_COOKIE[$key])) {
                return $_COOKIE[$key];
            }
            return $default;
        }

        /**
         * Gets the url parameters (including the leading controller and action) specified by the path.
         * @return Array
         */
        public function getParameters($offset = 0, $length = null, $val = null) {
            
            //Get the current url parts from the booter
            $params = $this->booter->urlParts;
            
            //At least shift two params, because of Controller/Action
            for ($i = 0; $i < 2 + $offset; $i++) array_shift($params);
            
            //New keys as array_shift does not change them
            $params = array_values($params);

            //Compare with default value
            if ($length == 1 && !isset($params[0])) return false;
            if ($length == 1) return isset($val) ? $params[0] == $val : $params[0];

            //Slice the resulting array according to 
            return array_slice($params, 0, $length);
        }

        /**
         * Gets the database communication stuff
         * @return Model
         */
        public function getModel() {
            return $this->booter->getModel(...func_get_args());
        }

        /**
         * Gets the data of the user who requested.
         */
        public function getRequestingUser() {
            return $this->booter->rqclient;
        }

        public function updateRequestingUser($id = null) {
            $updates = $this->getModel("z_login", $this->getZRoot())->getUserById($id !== null ? $id : $this->getRequestingUser()["id"]);
            foreach ($updates as $key => $value) {
                if (isset($this->booter->rqclient[$key])) $this->booter->rqclient[$key] = $value;
            }
        }

        public function getBooterSettings($key = null) {
            return $key !== null ? $this->booter->settings[$key] : $this->booter->settings;
        }

        public function getConfigFile() {
            return $this->booter->config_file;
        }

        public function getRootFolder() {
            return $this->booter->rootFolder;
        }

        /**
         * Gets the permission level of the requesting user.
         */
        public function getReqeustingUserPermissionLevel() {
            return $this->getRequestingUser()["permissionLevel"];
        }

        public function updateErrorHandling($state) {
            $this->booter->updateErrorHandling($state);
        }

        public function getPermissionNameByLevel($level) {
            $permissionLevelNames = $this->getModel("Employee")->getPermissionLevelNames();
            foreach ($permissionLevelNames as $permissionLevelName) {
                if ($permissionLevelName["value"] == $level) return $permissionLevelName["name"];
            }
        }

        public function upload() {
            require_once $this->getZRoot()."z_upload.php";
            return new z_upload($this);
        }

    }
?>