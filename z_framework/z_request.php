<?php 
    /**
     * Route handling system documentation:
     * Every action takes two parameters.
     * Request => used to get incoming and session stuff
     * Response => used to handle outgoing stuff
     */

    class Request extends RequestResponseHandler {

        /**
         * Gets a get parameter
         * @param String $key of the parameter
         * @param String $default Default value
         * @return Array|String Content of the get value
         */
        public function getGet($key, $default = null) {
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
            return $default;
        }

        /**
         * Gets a post parameter
         * @param String $key of the parameter
         * @param String $default Default value
         * @return Array|String Content of the post value
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
        public function getCookie($key, $default = null) {
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
         * @return z_model
         */
        public function getModel() {
            return $this->booter->getModel(...func_get_args());
        }

        /**
         * Gets the user who requested.
         * @return User
         */
        public function getRequestingUser() {
            return $this->booter->user;
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

        public function updateErrorHandling($state) {
            $this->booter->updateErrorHandling($state);
        }

        public function upload() {
            require_once $this->getZRoot()."z_upload.php";
            return new z_upload($this);
        }

        public function checkPermission($permission) {
            $user = $this->getRequestingUser();
            if (!$user->isLoggedIn) {
                $this->booter->executePath(["login", "index"]);
                exit;
            }
            if (!$user->checkPermission($permission)) {
                $this->booter->executePath(["error", "403"]);
                exit;
            }
        }

        /**
         * Validates form data from the client
         * @param String $method Method to use for checks ("POST"|"GET")
         * @return Array|Boolean A list of errors or true
         */
        public function validateForm($fields, $data = null) {
            $errors = [];

            if ($data == null) {
                $data = $_POST;
            }

            $formResult = new FormResult($this);
            $formResult->fields = $fields;

            foreach ($fields as $field) {
                $name = $field->name;
                foreach ($field->rules as $rule) {
                    $type = $rule["type"];

                    if ($type == "required") {
                        if (!isset($data[$name]) || $data[$name] == "") {
                            $errors[] = ["name" => $name, "type" => "required"];
                        }
                    } else if (isset($data[$name])) {
                        $value = $data[$name];

                        if ($type == "length") {
                            $len = strlen($value);
                            if ($len < $rule["min"] || $len > $rule["max"]) {
                                $errors[] = ["name" => $name, "type" => "length"];
                            }
                        } else if ($type == "filter") {
                            if (!filter_var($value, $rule["filter"])) {
                                $errors[] = ["name" => $name, "type" => "filter"];
                            }
                        } else if ($type == "unique") {
                            if (isset($rule["ignoreField"])) {
                                if (!$this->booter->z_db->checkIfUnique($rule["table"], $rule["field"], $value, $rule["ignoreField"], $rule["ignoreValue"])) {
                                    $errors[] = ["name" => $name, "type" => "unique"];
                                }
                            } else {
                                if (!$this->booter->z_db->checkIfUnique($rule["table"], $rule["field"], $value)) {
                                    $errors[] = ["name" => $name, "type" => "unique"];
                                }
                            }
                        } else if ($type == "exist") {
                            if (!$this->booter->z_db->checkIfExists($rule["table"], $rule["field"], $value)) {
                                $errors[] = ["name" => $name, "type" => "exist"];
                            }
                        } else if ($type == "integer") {
                            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                                $errors[] = ["name" => $name, "type" => "integer"];
                            }
                            $value = intval($value);
                        } else if ($type == "range") {
                            if ($value < $rule["min"] || $value > $rule["max"]) {
                                $errors[] = ["name" => $name, "type" => "range"];
                            }
                        } else {
                            $errors[] = ["name" => $name, "type" => "contact_admin"]; //Unkown type
                        }

                        $field->value = $value;
                    }
                }

            }

            $formResult->errors = $errors;
            $formResult->hasErrors = count($errors) > 0;
            return $formResult;
        }

        /**
         * Checks if the request contains form data. When it contains form data, methods like validateForm() can be used.
         * @return Boolean
         */
        public function hasFormData() {
            return isset($_POST["isFormData"]);
        }

        /**
         * Checks if the request is a async ajax request of a type
         * @param String $type
         * @return Boolean
         */
        public function isAction($type) {
            return ($this->getPost("action") == $type);
        }

        /**
         * Validates a "Create Edit Delete" input
         */
        public function validateCED($name, $rules) {
            $errors = [];

            $result = new FormResult();

            if (isset($_POST[$name])) {
                $array = $_POST[$name];
                foreach ($array as $i => $subform) {
                    $subresult = $this->validateForm($rules, $subform);
                    $suberrors = $subresult->errors;
                    foreach ($suberrors as $suberror) {
                        $suberror["subname"] = $suberror["name"];
                        $suberror["name"] = $name;
                        $suberror["index"] = $i;
                        $errors[] = $suberror;
                    }
                }
            } else {
                $result->doNothing = true;
            }
            
            $result->fields = $rules;
            $result->hasErrors = count($errors) > 0;
            $result->errors = $errors;
            $result->name = $name;
            return $result;
        }

    }

    class FormResult {

        function __construct()
        {
            $this->doNothing = false; //Set when no CED action is present
            $this->hasErrors = false;
            $this->errors = [];
            $this->fields = [];
        }
    }

    class FormField {
        function __construct($name, $dbName = null) {
            $this->rules = [];
            $this->name = $name;
            $this->dbField = $dbName ? $dbName : $name;
            $this->dataType = "s";
        }

        function filter($filter) {
            $this->rules[] = ["name" => $this->name, "type" => "filter", "filter" => $filter];
            return $this;
        }

        function unique($table, $field, $ignoreField = null, $ignoreValue = null) {
            $this->rules[] = ["name" => $this->name, "type" => "unique", "table" => $table, "field" => $field, "ignoreField" => $ignoreField, "ignoreValue" => $ignoreValue];
            return $this;
        }

        function exists($table, $field) {
            $this->rules[] = ["name" => $this->name, "type" => "exist", "table" => $table, "field" => $field];
            return $this;
        }

        function required() {
            $this->rules[] = ["name" => $this->name, "type" => "required"];
            return $this;
        }

        function length($min, $max) {
            $this->rules[] = ["name" => $this->name, "type" => "length", "min" => $min, "max" => $max];
            return $this;
        }

        function integer() {
            $this->rules[] = ["name" => $this->name, "type" => "integer"];
            $this->dataType = "i";
            return $this;
        }

        function range($min, $max) {
            $this->rules[] = ["range" => $this->name, "type" => "range", "min" => $min, "max" => $max];
            return $this;
        }
    }
?>