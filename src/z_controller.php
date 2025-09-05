<?php
    /**
     * Holds the base controller class
     */

    /**
     * Base class for all controllers. Controllers should inherit from this class.
     */
    class z_controller { 

        /**
         * Makes food edible for input selects
         * @param array $table Database table result
         * @param string $valueField Row that is used as value
         * @param string $textField Row that is shown to the client as text
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
            return json_encode($food);
        }

        /**
         * Makes food edible for CED fields
         * @param array $table Table
         * @param array $fields Fields of the array to get to the client
         */
        public function makeCEDFood($table, $fields, $escape = null) {
            $str = "[";
            foreach ($table as $row) {
                $str .= '{"dbId": "' . $row["id"].'"';
                foreach ($fields as $field) {
                    if($escape === null) {
                        $str .= ',"'.$field.'":"'.$row[$field].'"';
                    } else {
                        $str .= ',"'.$field.'":"' . $escape($row[$field], $field) . '"';
                    }
                }
                $str .= "},";
            }
            return $str . "]";
        }

    }

?>
