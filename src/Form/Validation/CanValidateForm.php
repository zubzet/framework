<?php

    namespace ZubZet\Framework\Form\Validation;

    use ZubZet\Framework\Form\Validation\Field;
    use ZubZet\Framework\Form\Validation\Result;

    trait CanValidateForm {

        /**
         * Validates form data from the client
         * @param Field[] $fields Array of fields with the validation rules
         * @param array $data Input for the validation. getGet() or getPost() can be used here as parameters
         * @return Result A result to work with
         */
        public function validateForm($fields, $data = null) {
            $errors = [];

            if ($data == null) {
                $data = array_merge(
                    $this->getPost(),
                    $this->getFiles(),
                );
            }

            $formResult = new Result();
            $formResult->fields = $fields;

            foreach ($fields as $field) {
                $name = $field->name;

                $field->value = $data[$name] ?? null;

                foreach ($field->rules as $rule) {
                    $type = $rule["type"];

                    if ($type == "required") {

                        if (!((isset($data[$name]) && $data[$name] != "" ) || isset($this->getFiles()[$name]))) {
                            $errors[] = ["name" => $name, "type" => "required"];
                        } else {
                            $value = $data[$name];
                            $field->value = $value; //Require needs to be the first rule or this line could break something!
                        }
                    } else if (isset($data[$name]) && (!empty($data[$name]) || $data[$name] == "0")) {
                        $value = $data[$name];

                        if ($type == "length") {
                            // For array values (e.g. multi-select) length is
                            // the item count; for scalar values it's strlen.
                            $len = is_array($value) ? count($value) : strlen($value);
                            if ($len < $rule["min"] || $len > $rule["max"]) {
                                $errors[] = ["name" => $name, "type" => "length", "info" => [$rule["min"], $rule["max"]]];
                            }
                        } else if ($type == "filter") {
                            if (!filter_var($value, $rule["filter"])) {
                                $errors[] = ["name" => $name, "type" => "filter"];
                            }
                        } else if ($type == "unique") {
                            if (isset($rule["ignoreField"])) {
                                if (!db()->checkIfUnique($rule["table"], $rule["field"], $value, $rule["ignoreField"], $rule["ignoreValue"])) {
                                    $errors[] = ["name" => $name, "type" => "unique"];
                                }
                            } else {
                                if (!db()->checkIfUnique($rule["table"], $rule["field"], $value)) {
                                    $errors[] = ["name" => $name, "type" => "unique"];
                                }
                            }
                        } else if ($type == "exist") {
                            // Array values: every item must exist in the
                            // configured (table, field).
                            $items = is_array($value) ? $value : [$value];
                            foreach ($items as $item) {
                                if (!db()->checkIfExists($rule["table"], $rule["field"], $item)) {
                                    $errors[] = ["name" => $name, "type" => "exist"];
                                    break;
                                }
                            }
                        } else if ($type == "in") {
                            // Array values: every item must be in the
                            // configured in-memory allow-list.
                            $items = is_array($value) ? $value : [$value];
                            foreach ($items as $item) {
                                if (!in_array($item, $rule["allowedValues"])) {
                                    $errors[] = ["name" => $name, "type" => "in"];
                                    break;
                                }
                            }
                        } else if ($type == "regex") {
                            // For array values the regex is applied per item;
                            // the field fails as soon as any item fails.
                            $items = is_array($value) ? $value : [$value];
                            foreach ($items as $item) {
                                $tmp_value = $item;
                                foreach ($rule["exceptions"] as $exception) {
                                    $tmp_value = str_replace($exception, "", $tmp_value);
                                }
                                if (!preg_replace($rule["expression"], "", $tmp_value) != $tmp_value) {
                                    $errors[] = ["name" => $name, "type" => "regex"];
                                    break;
                                }
                            }
                        } else if ($type == "integer") {
                            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
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
                            if (isset($this->getFiles()[$name])) {
                                $file = $this->getFiles()[$name];
                                if ($file["size"] > $rule["maxSize"]) {
                                    $errors[] = ["name" => $name, "type" => "file_to_big"];
                                }

                                $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

                                if(isset($rule["types"]) && !empty($rule["types"]) && !in_array($extension, $rule["types"])) {
                                    $errors[] = ["name" => $name, "type" => "file_type"];
                                }
                            } else {
                                $errors[] = ["name" => $name, "type" => "file"];
                            }
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


    }

?>