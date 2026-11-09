<?php

    namespace ZubZet\Framework\Form\Validation;

    /**
     * Represents an input field of a form on the server side
     */
    class Field {

        /**
         * @var array[] $rules List of rules that specify how to validate this form field. Each rule is an associative array with `name`, `type` and rule-specific keys.
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
         * Creates an error when `filter_var()` returns a falsy value (`false`, `0`, `""`, `null`), so any `FILTER_*` constant works but no options or flags can be passed.
         *
         * Does not support array values (e.g. `multi-select`) — `filter_var()`
         * expects a scalar; pair `multi-select` with `->in()` instead.
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
         * Does not support array values (e.g. `multi-select`) — the rule
         * runs a single uniqueness query against a scalar value.
         *
         * @param string $table Table name in the database (inserted unescaped)
         * @param string $field Field name in the table (inserted unescaped)
         * @param string|null $ignoreField name of the field in which the ignore value is (inserted unescaped)
         * @param mixed|null $ignoreValue value of the dataset that should be ignored
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
         * This rule checks if a dataset with a specific value already exists
         * in the database. For array-valued fields (e.g. `multi-select`) the
         * rule is applied per item, every picked entry must exist.
         *
         * @param string $table Table name in the database (inserted unescaped)
         * @param string $field Field name in the table (inserted unescaped)
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
         * Adds an `in` rule
         *
         * The submitted value must appear in the given in-memory allow-list.
         * Unlike `exists()` this does not hit the database. Useful to guard
         * `select` / `multi-select` fields against tampered POST payloads
         * where the client sent an option that wasn't in the rendered
         * dropdown. For array-valued fields the rule is applied per item:
         * every picked entry must be in the allow-list.
         *
         * @param array $allowedValues The list of allowed values
         * @return Field Returns itself to allow chaining
         */
        function in(array $allowedValues) {
            $this->rules[] = [
                "name" => $this->name,
                "type" => "in",
                "allowedValues" => $allowedValues,
            ];
            return $this;
        }

        /**
         * Adds a "must be ticked" rule for checkboxes.
         *
         * A `checkbox` field always submits "1" (ticked) or "0" (unticked),
         * so `required()` is a no-op for it. Use this to enforce that the box
         * is actually ticked (e.g. accept-terms). Sugar over `in()` with the
         * common truthy representations. Skipped when the key is missing from
         * the request (non-Z.js clients), so chain `required()` to enforce it.
         *
         * @return Field Returns itself to allow chaining
         */
        function checked() {
            return $this->in(["1", "true", "on"]);
        }

        /**
         * Adds a required rule
         * 
         * With this rule an error is created when no input for this field is given.
         * It must be the first rule on a field, otherwise it overwrites the value cast by earlier rules like integer() with the raw input.
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
         * This rule will create an error when the input is too long or too short.
         *
         * For array-valued fields (e.g. `multi-select`) length is measured as
         * the item count (`count($value)`) instead of the byte count (`strlen()`), so
         * `->length(1, 3)` means "between 1 and 3 selections."
         *
         * @param int $min Minimum number of bytes (or items for arrays)
         * @param int $max Maximum number of bytes (or items for arrays)
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
         * Does not support array values (e.g. `multi-select`) — `filter_var()`
         * and `intval()` expect a scalar.
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
         * Does not support array values (e.g. `multi-select`) — the comparison
         * (`$value < $min`) expects a scalar.
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
         * This rule checks if the input value is a date that `strtotime()` can parse.
         *
         * Does not support array values (e.g. `multi-select`) — `strtotime()`
         * expects a scalar.
         *
         * @param string $format Currently unused, the format is not enforced
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
         * Errors: file (no upload), file_to_big (above $maxSize), file_type (extension not in $types).
         *
         * @param integer $maxSize The maximum allowed file size in bytes. Constants for this are available.
         * @param string[] $types Accepted lowercase file extensions without dot, e.g. ["png", "jpg"]; an empty array allows any type
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
         * The `$exceptions` are stripped from the value first, then every match of `$expression` is removed (preg_replace, not preg_match); the value passes only if nothing is left.
         *
         * For array-valued fields (e.g. `multi-select`) the regex is applied
         * per item; the field fails as soon as any single item fails.
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