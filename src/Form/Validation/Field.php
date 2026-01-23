<?php

    namespace ZubZet\Framework\Form\Validation;

    /**
     * Represents an input field of a form on the server side
     */
    class Field {

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
         * @var boolean $isFile Mark a form field as a file
         */
        public bool $isFile = false;

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
            $this->noSave = false;
        }

        /**
         * Adds a filter rule
         * 
         * Creates an error when a filter fails. All filter_var compatible filters are available.
         * 
         * @param int $filter A valid PHP filter
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
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
         * @return Field Returns itself to allow chaining
         */
        function file($maxSize, $types = []) {
            $this->isFile = true;

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
         * @return Field Returns itself to allow chaining
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