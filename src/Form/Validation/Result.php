<?php

    namespace ZubZet\Framework\Form\Validation;

    use ZubZet\Framework\Form\Validation\Field;

    /**
     * Holds the result of a validation of a form
     */
    class Result {

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
         * @var Field[] $fields Array of the validated fields.
         */
        public $fields;

         /**
         * @var string $name Holds the name of a form result. Used for CED forms
         */
        public ?string $name;

        /**
         * Creates a new FormResult object
         */
        function __construct() {
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

?>