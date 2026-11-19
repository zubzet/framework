<?php

    namespace ZubZet\Framework\Core;

    /**
     * Holds the base controller class
     */

    /**
     * Base class for all controllers. Controllers should inherit from this class.
     */
    class Controller {

        /**
         * Makes food edible for input selects
         * @param array $table Database table result
         * @param string $valueField Column key that is used as value
         * @param string $textField Column key that is shown to the client as text
         * @param string|null $optionalTextField Column key appended to the text with a space
         * @return string JSON-encoded list of value/text pairs
         */
        public function makeFood($table, $valueField, $textField, $optionalTextField = null) {
            $food = [];
            foreach ($table as $row) {
                $text = $row[$textField];
                if(!is_null($optionalTextField)) $text .= " " . $row[$optionalTextField];

                $food[] = [
                    "value" => $row[$valueField],
                    "text" => $text,
                ];
            }
            return json_encode($food, JSON_HEX_TAG | JSON_HEX_AMP);
        }

        /**
         * Makes food edible for CED fields
         * @param array $table Table
         * @param array $fields Fields of the array to get to the client
         * @param callable|null $escape Optional callable($value, $field) returning the escaped value, output as is between double quotes; without it values are encoded as JS strings
         * @return string JS array literal (not valid JSON, trailing commas) with one object per row; every row needs an 'id' (output as 'dbId')
         */
        public function makeCEDFood($table, $fields, $escape = null) {
            // Values come from users, they must neither end the string nor close the script tag
            $encode = fn($value) => json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);

            $str = "[";
            foreach ($table as $row) {
                $str .= '{"dbId": ' . $encode($row["id"]);
                foreach ($fields as $field) {
                    $value = is_null($escape) ? $encode($row[$field]) : '"' . $escape($row[$field], $field) . '"';
                    $str .= ',' . $encode($field) . ':' . $value;
                }
                $str .= "},";
            }
            return $str . "]";
        }

    }

?>
