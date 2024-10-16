<?php
    /**
     * Route handling system documentation:
     * Every action takes two parameters.
     * Request => used to get incoming and session stuff
     * Response => used to handle outgoing stuff
     */

    /**
     * Base class for Response and Request
     */
    class Request extends RequestResponseHandler {

        /**
         * @var array Store values within the Request to pass through data within internal redirects
         */
        public array $store = [];

        /**
         * Gets a GET parameter
         * @param string $key The key of the parameter
         * @param mixed $default Default value
         * @return string|mixed The content of the GET value
         */
        public function getGet($key, $default = null) {
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
            return $default;
        }

        /**
         * Gets a POST parameter
         * @param string $key The key of the parameter
         * @param mixed $default Default value
         * @return string|mixed The content of the POST value
         */
        public function getPost($key, $default = null) {
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
            return $default;
        }

        /**
         * Gets the IP of a request
         * @return bool|string The IP of the client. False if no IP is detected
         */
        public function ip() {
            $ip = null;
            if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if(!empty($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            } else if (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else if(getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } else if(getenv('HTTP_X_FORWARDED')) {
                $ip = getenv('HTTP_X_FORWARDED');
            } else if(getenv('HTTP_FORWARDED_FOR')) {
                $ip = getenv('HTTP_FORWARDED_FOR');
            } else if(getenv('HTTP_FORWARDED')) {
                $ip = getenv('HTTP_FORWARDED');
            } else if(getenv('REMOTE_ADDR')) {
                $ip = getenv('REMOTE_ADDR');
            }
            return $ip;
        }

        /**
         * Detects if a request was made from the console
         *
         * @return bool True if the request was made from a console
         */
        public function isCli() {
            if(defined('STDIN')) {
                return true;
            }

            $remoteAddr = empty($_SERVER['REMOTE_ADDR']);
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']);
            $args = count($_SERVER['argv'] ?? []);

            return $remoteAddr && !$userAgent && $args > 0;
        }

        public function referer() {
            return $_SERVER['HTTP_REFERER'] ?? null;
        }

        public function userAgent() {
            return $_SERVER['HTTP_USER_AGENT'] ?? null;
        }

        public function getExecutionTime() {
            if(!isset($_SERVER["REQUEST_TIME_FLOAT"])) return false;
            return microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        }

        /**
         * Gets a posted file
         * @param string $key The name of the file
         * @param mixed $default Default value if the file is not posted
         * @return string|mixed The posted file
         */
        public function getFile($key, $default = null) {
            if (isset($_FILES[$key])) {
                return $_FILES[$key];
            }
            return $default;
        }

        /**
         * Gets a cookie
         * @param string $key The key of the parameter
         * @param mixed $default Default value
         * @return mixed Content of the cookie
         */
        public function getCookie($key, $default = null) {
            if (isset($_COOKIE[$key])) {
                return $_COOKIE[$key];
            }
            return $default;
        }

        /**
         * Returns a list of all visited controllers
         * @return string[] All visited controllers as an array
         */
        public function getControllerStack() {
            return $this->booter->ControllerStack;
        }

        /**
         * Returns the last controller visited before the current one
         * @return string The last controller visited before the current one
         */
        public function getLastController() {
            if(isset($this->booter->ControllerStack[count($this->booter->ControllerStack) - 2])) {
                return $this->booter->ControllerStack[count($this->booter->ControllerStack) - 2];
            }
            return false;
        }

        /**
         * Returns a list of all visited actions
         * @return string[] All visited actions as an array
         */
        public function getActionStack() {
            return $this->booter->ActionStack;
        }

        /**
         * Returns the current URL
         * @return string The actual URL that was requested including parameters and host
         */
        public function getCurrentURL() {
            return $this->booter->host.$this->booter->url;
        }

        /**
         * Returns the current root URL including protocol and root directory
         * @return string a URL like $opt["root"] but including the host before
         */
        public function getRoot() {
            return $this->booter->root;
        }

        /**
         * Returns the app domain as specified in the configuration (`host=`)
         * @return string the domain
         */
        public function getDomain(): string {
            return explode(
                ":",
                str_replace(
                    ["http://", "https://", "/"],
                    "",
                    (string) $this->getBooterSettings("host"),
                ),
            )[0];
        }

        /**
         * Returns the last action visited before the current one
         * @return string The last action visited before the current one
         */
        public function getPreAction() {
            if(isset($this->booter->ActionStack[count($this->booter->ActionStack) - 2])) {
                return $this->booter->ActionStack[count($this->booter->ActionStack) - 2];
            }
            return false;
        }

        /**
         * Gets the URL parameters (including the leading controller and action) specified by the path.
         * @param int $offset The offset from which to start. Can be -1 if action_fallback is used
         * @param int $length The amount of array elements that will be returned at the set offset. If null, every element will be returned
         * @param string $val If the length is 1, a Boolean will be returned. $val will be compared to the parameter
         * @return mixed[]|string
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
         * A backend implementation of the Google reCAPTCHA v3 API
         * @param string $response The response you have received from the Google reCAPTCHA execution
         * @param string $action The action name used in reCAPTCHA verification
         * @param string $secretKey Your reCAPTCHA secret key. (Can be retrieved from https://www.google.com/recaptcha/intro/v3.html)
         * @param bool $checkHostname Whether to check if the hostname matches
         * @return float The score between 0-1 returned by Google
         */
        public function getReCaptchaV3Score($response, $action, $secretKey, $checkHostname = true) {
            // Build POST request:
            $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

            try {

                // Make and decode POST request:
                $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $secretKey . '&response=' . $response );
                $recaptcha = json_decode($recaptcha);

                //Make sure the actions are the same
                if($recaptcha->action != $action) return 0;

                //Make sure the hostname is the same
                if($checkHostname) {
                    //Remove paths and www subdomains to prevent errors
                    function cleanHostname($hostname) {
                        $hostname = trim($hostname, '/');
                        $hostname = str_replace('http://', '', $hostname);
                        $hostname = str_replace('https://', '', $hostname);
                        return preg_replace('/^www\./', '', $hostname);
                    }
                    if(cleanHostname($recaptcha->hostname) !== cleanHostname($this->getBooterSettings("host"))) {
                        return 0;
                    }
                }

                //TODO: Add a time limit

                // Take action based on the score returned:
                return $recaptcha->score;

            //Return a score of zero if the checks produce errors (Usually a relay attack)
            } catch(Exception $ex) {
                return 0;
            }
        }

        /**
         * Works like getParameters and decodes an SEO optimized URL. Example: test.com/episodes/this-is-some-text-64 The 64 is an id
         * @param int $offset The offset from which to start. Can be -1 if action_fallback is used
         * @return string[] [id, text] of the URL
         */
        public function getReadableParameter($offset = 0) {
            $param = $this->getParameters($offset, 1);
            $param = explode("-", $param);
            $id = $param[count($param) - 1];
            array_pop($param);
            return ["id" => $id, "text" => implode("-", $param)];
        }

        /**
         * Gets the user who made the request.
         * @return User Object of the requesting user
         */
        public function getRequestingUser() {
            return $this->booter->user;
        }

        /**
         * Gets the relative path to the config file of the framework
         * @return string Relative path to the config file
         */
        public function getConfigFile() {
            return $this->booter->config_file;
        }

        /**
         * Gets the path to the root folder of the project.
         * @return string Path to the root folder
         */
        public function getRootFolder() {
            return $this->booter->rootFolder;
        }

        /**
         * Updates the error handling state.
         * @param int $state Error state. See: z_framework::updateErrorHandling($state)
         */
        public function updateErrorHandling($state) {
            $this->booter->updateErrorHandling($state);
        }

        /**
         * Checks if the current user has a permission. If the user is not logged in, they will be redirected to the login page. 
         * If the user is logged in but does not have the permission, they will be redirected to 403.
         * @param string $permission Permission to check for
         * @param bool $boolResult If true, the function will return a boolean result instead of redirecting
         */
        public function checkPermission($permission, $boolResult = false) {
            $user = $this->getRequestingUser();

            if($permission == "console") {
                if($this->isCli()) return true;
                if($boolResult) return false;
                $this->booter->executePath(["error", "403"]);
                exit; 
            }

            if (!$user->isLoggedIn) {
                if($boolResult) return false;
                $this->booter->executePath(["login", "index"]);
                exit;
            }

            if (!$user->checkPermission($permission)) {
                if($boolResult) return false;
                $this->booter->executePath(["error", "403"]);
                exit;
            }

            return true;
        }

        /**
         * Validates form data from the client
         * @param FormField[] $fields Array of fields with the validation rules
         * @param array $data Input for the validation. $_GET or $_POST can be used here as parameters
         * @return FormResult A result to work with
         */
        public function validateForm($fields, $data = null) {
            $errors = [];

            if ($data == null) {
                $data = array_merge($_POST, $_FILES);
            }

            $formResult = new FormResult($this);
            $formResult->fields = $fields;
   
            foreach ($fields as $field) {
                $name = $field->name;

                $field->value = $data[$name]??null;
                
                foreach ($field->rules as $rule) {
                    $type = $rule["type"];
                    
                    if ($type == "required") {
                        
                        if (!((isset($data[$name]) && $data[$name] != "" ) || isset($_FILES[$name]))) {
                            $errors[] = ["name" => $name, "type" => "required"];
                        } else {
                            $value = $data[$name];
                            $field->value = $value; //Require needs to be the first rule or this line could break something!
                        }
                    } else if (isset($data[$name]) && (!empty($data[$name]) || $data[$name] == "0")) {
                        $value = $data[$name];

                        if ($type == "length") {
                            $len = strlen($value);
                            if ($len < $rule["min"] || $len > $rule["max"]) {
                                $errors[] = ["name" => $name, "type" => "length", "info" => [$rule["min"], $rule["max"]]];
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
                        } else if ($type == "regex") {
                            $tmp_value = $value;
                            foreach ($rule["exceptions"] as $exception) {
                                $tmp_value = str_replace($exception, "", $tmp_value);
                            }
                            if (!preg_replace($rule["expression"], "", $tmp_value) != $tmp_value) {
                                $errors[] = ["name" => $name, "type" => "regex"];
                            }
                        } else if ($type == "integer") {
                            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                                $errors[] = ["name" => $name, "type" => "integer"];
                            }
                            $value = intval($value);
                        } else if ($type == "range") {
                            if ($value < $rule["min"] || $value > $rule["max"]) {
                                $errors[] = ["name" => $name, "type" => "range", "info" => [$rule["min"], $rule["max"]]];
                            }
                        } else if ($type == "date") { 
                            if (strtotime($value) == false) {
                                $errors[] = ["name" => $name, "type" => "date"];
                            }
                        } else if ($type == "file") {
                            if (isset($_FILES[$name])) {
                                $file = $_FILES[$name];
                                if ($file["size"] > $rule["maxSize"]) {
                                    $errors[] = ["name" => $name, "type" => "file_to_big"];
                                }
                            } else {
                                $errors[] = ["name" => $name, "type" => "file"];
                            }
                        } else {
                            $errors[] = ["name" => $name, "type" => "contact_admin"]; //Unknown type
                        }

                        $field->value = $value;
                    } else {
                        $field->value = null;
                    }
                }

            }

            $formResult->errors = $errors;
            $formResult->hasErrors = count($errors) > 0;
            return $formResult;
        }

        /**
         * Checks if the request contains form data. When it contains form data, methods like validateForm() can be used.
         * @return bool
         */
        public function hasFormData() {
            return isset($_POST["isFormData"]);
        }

        /**
         * Checks if the request is an async AJAX request of a given type
         * @param string $type Type of the request. Request is sent via Z.js => Z.Request.action()
         * @return bool True if the request is of the specified type
         */
        public function isAction($type) {
            return ($this->getPost("action") == $type);
        }

        /**
         * Validates a "Create Edit Delete" input
         * @param string $name Name of the input field
         * @param object $rules Array of rules for validating
         * @return FormResult Result of the validation. Needed to perform response actions
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

    /**
     * Holds the result of a validation of a form
     */
    class FormResult {

        /**
         * @var bool $doNothing When set, no CED action will be performed when this object is given into one.
         */
        public $doNothing;

        /**
         * @var bool $hasErrors Set when the input data was invalid
         */
        public $hasErrors;

        /**
         * @var object[] $errors Holds the errors of the validation
         */
        public $errors;

        /**
         * @var FormField[] $fields Array of the validated fields.
         */
        public $fields;

        /**
         * Creates a new FormResult object
         */
        function __construct()
        {
            $this->doNothing = false; //Set when no CED action is present
            $this->hasErrors = false;
            $this->errors = [];
            $this->fields = [];
        }

        /**
         * Gets a validated input value
         * @param string $name Name of the field to get the value from
         */
        function getValue($name) {
            foreach ($this->fields as $field) {
                if ($field->name == $name) return $field->value;
            }
            return null;
        }

        /**
         * Adds a custom error to the form result
         * @param string $name Name of the form field to assign the error to
         * @param string $type Type of the error. Influences the error message
         */
         function addCustomError($name, $type) {
            $this->errors[] = ["name" => $name, "type" => $type];
            $this->hasErrors = true;
        }
    }

    /**
     * Represents an input field of a form on the server side
     */
    class FormField {

        /**
         * @var object[] $rules Array of rules that specify how to validate this form field.
         */
        public $rules;

        /**
         * @var string $name Name of the input in the post request
         */
        public $name;

        /**
         * @var string $dbField Name of the field in the database
         */
        public $dbField;

        /**
         * @var string $dataType Datatype needed for prepared statements (s/i...)
         */
        public $dataType;

        /**
         * @var boolean $isRequired If set, the value is required. This is saved outside the rules array because other rules may need access to this value to work properly
         */
        public $isRequired;

        /**
         * @var mixed $value The validated value
         */
        public $value;

        /**
         * @var boolean $noSave Skip this field when writing SQL 
         */
        public $noSave;

        /**
         * Creates a form field representation
         * @param string $name Name of the field. Should match the name in the post header
         * @param string $dbName Name of the field in the database. If not set it will be equal to the name
         */
        function __construct($name, $dbName = null) {
            $this->rules = [];
            $this->name = $name;
            $this->dbField = isset($dbName) ? $dbName : $name;
            $this->dataType = "s";
            $this->isRequired = false;
            $this->value = null;
            $this->isFile = false;
            $this->noSave = false;
        }

        /**
         * Adds a filter rule
         * 
         * Creates an error when a filter fails. All filter_var compatible filters are available.
         * 
         * @param int $filter A valid PHP filter
         * @return FormField Returns itself to allow chaining
         */
        function filter($filter) {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "filter", 
                "filter" => $filter
            ];
            return $this;
        }

        /**
         * Adds a unique rule
         * 
         * This rule checks if a dataset with a specific value already exists.  
         * A set to ignore can also be specified. An error will be created when the set exists.
         * 
         * @param string $table Table name in the database
         * @param string $field Field name in the table
         * @param string $ignoreField name of the field in which the ignore value is
         * @param string $ignoreValue value of the dataset that should be ignored
         * @return FormField Returns itself to allow chaining
         */
        function unique($table, $field, $ignoreField = null, $ignoreValue = null) {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "unique", 
                "table" => $table, 
                "field" => $field, 
                "ignoreField" => $ignoreField, 
                "ignoreValue" => $ignoreValue
            ];
            return $this;
        }

        /**
         * Adds an exists rule
         * 
         * This rule checks if a dataset with a specific value already exists.  
         * A set to ignore can also be specified. An error will be created when the set does not exist.
         * 
         * @param string $table Table name in the database
         * @param string $field Field name in the table
         * @return FormField Returns itself to allow chaining
         */
        function exists($table, $field) {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "exist", 
                "table" => $table, 
                "field" => $field
            ];
            return $this;
        }

        /**
         * Adds a required rule
         * 
         * With this rule an error is created when no input for this field is given.
         * 
         * @return FormField Returns itself to allow chaining
         */
        function required() {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "required"
            ];
            $this->isRequired = true;
            return $this;
        }

        /**
         * Adds a length rule
         * 
         * This rule will create an error when the input is too long or too short
         * 
         * @param int $min Minimum number of chars
         * @param int $max Maximum number of chars
         * @return FormField Returns itself to allow chaining
         */
        function length($min, $max) {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "length", 
                "min" => $min, 
                "max" => $max
            ];
            return $this;
        }

        /**
         * Adds an integer rule
         * 
         * This rule will create an error when the input was not a value that could be parsed to an integer.
         * Also this function sets the type of the field to "i".
         * 
         * @return FormField Returns itself to allow chaining
         */
        function integer() {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "integer"
            ];
            $this->dataType = "i";
            return $this;
        }

        /**
         * Adds a range rule
         * 
         * This rule checks if the input, as a number, is within a range. If the number is not in the range, an error will be created.
         * 
         * @param float $min Min allowed value
         * @param float $max Max allowed value
         * @return FormField Returns itself to allow chaining
         */
        function range($min, $max) {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "range", 
                "min" => $min, 
                "max" => $max
            ];
            return $this;
        }

        /**
         * Adds a date rule
         * 
         * This rule checks if the input value adheres to a given date format
         * 
         * @param string $format The tested date format
         * @return FormField Returns itself to allow chaining
         */
        function date($format = "Y-m-d") {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "date", 
                "format" => $format
            ];
            return $this;
        }

        /**
         * Rule for validating file uploads. This rule must also be set when automatic uploads in insertDatabase is needed.
         * 
         * @param integer $maxSize The maximum allowed file size. Constants for this are available.
         * @param string[] $types Accepted file types
         * @return FormField Returns itself to allow chaining
         */
        function file($maxSize, $types) {
            $this->isFile = true;
            $this->fileMaxSize = $maxSize;
            $this->fileTypes = $types;

            $this->rules[] = [
                "name" => $this->name, 
                "type" => "file", 
                "types" => $types,
                "maxSize" => $maxSize
            ];
            return $this;
        }

        /**
         * Adds a regular expression rule to the field
         * 
         * @param string $expression The regex expression
         * @param string[] $exceptions An array of characters to be excluded from the regex
         * @return FormField Returns itself to allow chaining
         */
        function regex($expression, $exceptions = []) {
            $this->rules[] = [
                "name" => $this->name, 
                "type" => "regex", 
                "expression" => $expression,
                "exceptions" => $exceptions
            ];
            return $this;
        }

    }
?>
