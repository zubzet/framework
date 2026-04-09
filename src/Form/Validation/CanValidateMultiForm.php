<?php

    namespace ZubZet\Framework\Form\Validation;

    use ZubZet\Framework\Form\Validation\Result;

    trait CanValidateMultiForm {

        use CanValidateForm;

        /**
         * Validates a "Create Edit Delete" input
         * @param string $name Name of the input field
         * @param array $rules Array of rules for validating
         * @return Result Result of the validation. Needed to perform response actions
         */
        public function validateCED($name, $rules) {
            $errors = [];

            $result = new Result();

            $array = $this->getPost($name);
            if(is_array($array)) {
                foreach ($array as $i => $subform) {
                    $subResult = $this->validateForm($rules, $subform);
                    $subErrors = $subResult->errors;
                    foreach ($subErrors as $subError) {
                        $subError["subname"] = $subError["name"];
                        $subError["name"] = $name;
                        $subError["index"] = $i;
                        $errors[] = $subError;
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

?>