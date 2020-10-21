<?php

    class ConsoleContext {
        public $args = [];
        public function __construct() {
            global $argv;
            $arguments = array_slice($argv, 2);

            foreach($arguments as $argument) {
                if(substr($argument, 0, 1) != "-") {
                    $this->args[] = $argument;
                }
            }
        }

        public function matchArg($name) {
            return in_array($name, $this->args);
        }

        public function getArg($index) {
            return $this->args[$index];
        }
    }

?>