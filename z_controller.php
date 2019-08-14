<?php
    /**
     * Holds the base controller class
     */

    /**
     * Base class for all controllers. Controllers should inherit from this
     */
    class z_controller { 

        /**
         * Makes food edible to input selects
         * @param String $table Database table result
         * @param String $value_field Row that is used as value
         * @param String $text_field Row that is shown to the client as text
         */
        public function makeFood($table, $value_field, $text_field) {
            $str = [];
            foreach ($table as $row) {
                $str[] = '{ "value": "' . $row[$value_field] . '", "text": "' . $row[$text_field] . '"}';
            }
            return "[" . implode(",", $str) . "]";
        }

        /**
         * Makes food edible for CED fields
         * @param String $table Table
         * @param Array $fields Fields of the array to get to the client
         */
        public function makeCEDFood($table, $fields) {
            $str = "[";
            foreach ($table as $row) {
                $str .= '{dbId: ' . $row["id"];
                foreach ($fields as $field) {
                    $str .= ",$field:'" . $row[$field] . "'";
                }
                $str .= "},";
            }
            return $str . "]";
        }

    }

?>