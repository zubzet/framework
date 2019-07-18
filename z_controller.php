<?php

    class z_controller { 

        public $permission;

        /**
         * Makes food edible to input selects
         */
        public function makeFood($table, $value_field, $text_field) {
            $str = "[";
            foreach ($table as $row) {
                $str .= '{ value: "' . $row[$value_field] . '", text: "' . $row[$text_field] . '"},';
            }
            return $str . "]";
        }

        /**
         * Makes food edible for CED fields
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