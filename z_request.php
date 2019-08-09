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
         * Gets a get parameter
         * @param string $key of the parameter
         * @param string $default Default value
         * @return Array|string Content of the get value
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
         * @return Array|string Content of the post value
         */
        public function getPost($key, $default = null) {
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
            return $default;
        }

        /**
         * Gets a posted file
         * @param string $key The name of the file
         * @param string $default Default value if the file is not posted
         * @return Array|string The posted file
         */
        public function getFile($key, $default = null) {
            if (isset($_FILES[$key])) {
                return $_FILES[$key];
            }
            return $default;
        }

        /**
         * Gets a cookie
         * @param string $key of the parameter
         * @param string $default Default value
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
         * @param int $offset The offset from which to start. Can be -1 if action_fallback is used
         * @param int $length The amount of array elements that will be returned at the set offset. If null, every element will be returned
         * @param string $val If the length is 1, a Boolean will be returned. $val will be compared to the parameter
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
         * Gets the user who requested.
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
         * Checks if the current has a permission. If the user is not logged in, it will be redirected to the login page. 
         * If the user is logged in but does not have the permission, it will be redirected to 403.
         * @param string $permission Permission to check for
         */
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
                $data = $_POST;
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
                        }
                    } else if (!empty($data[$name]) || $data[$name] == "0") {
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
                            $errors[] = ["name" => $name, "type" => "contact_admin"]; //Unkown type
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
         * Checks if the request is a async ajax request of a type
         * @param string $type Type of the request. Request is send via Z.js => Z.Request.action()
         * @return bool True if request of type
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
         * @var boolean $isRequired If set, the value is required. This is saved outside the rules to array becaue other rules may need access to this value to work properly
         */
        public $isRequired;

        /**
         * @var any $value The validated value
         */
        public $value;

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
        }

        /**
         * Adds a filter rule
         * 
         * Creates an error when a filter fails. Available are all filter_var compatibles.
         * 
         * @param int $filter A valid php filter
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
         * This rule checks if a dataset with specific value already exist.  
         * A set to ignore can also be specified. An error will be created when the set exists.
         * 
         * @param string $table Tablename in the database
         * @param string $field Fieldname in the table
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
         * Adds a exists rule
         * 
         * This rule checks if a dataset with specific value already exist.  
         * A set to ignore can also be specified. An error will be created when the set does not exists.
         * 
         * @param string $table Tablename in the database
         * @param string $field Fieldname in the table
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
         * This rule will create an error when the input is to long or to short
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
         * Adds a integer rule
         * 
         * This rule will create an error when the input was not an value that could be parsed to an integer.
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
         * This rule checks if the input as a number is in a range. If the number is not in the range, an error will be created.
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
         * This rule checks if the inputed value adheres to a given date format
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
    }
?>