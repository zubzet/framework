<?php

    define("COLOR_BLACK", "0;30");
    define("COLOR_GREEN", "0;32");
    define("COLOR_RED", "0;31");
    define("COLOR_BLUE", "0;34");
    define("COLOR_WHITE", "1;37");
    define("COLOR_PURPLE", "0;35");
    define("COLOR_BRONN", "0;33");
    define("COLOR_YELLOW", "1;33");
    define("COLOR_CYAN", "0;36");

    define("COLOR_DARK_GRAY", "1;30");

    define("COLOR_LIGHT_CYAN", "1;36");
    define("COLOR_LIGHT_RED", "1;31");
    define("COLOR_LIGHT_BLUE", "1;34");
    define("COLOR_LIGHT_GREEN", "1;32");
    define("COLOR_LIGHT_PURPLE", "1;35");
    define("COLOR_LIGHT_GRAY", "0;37");

    class Console {
        public static $defaultColor = COLOR_WHITE;
        public const NEW_LINE = true;

        public static function write($str, $newLine = true) {
            Console::generateColor(Console::$defaultColor);
            Console::formatString($str);
            Console::generateColor(Console::$defaultColor);
            if($newLine) echo "\n";
        }

        public static function echo($str) {
            Console::write($str, false);
        }

        public static function generateColor($color) {
            // Background: \033[48;5;57m
            echo "\033[".$color."m";
        }

        private static function formatString($str) {
            $str = str_split($str);
            $buffer = "";
            $gatheringColor = false;
            foreach ($str as $i => $char) {
                if(!$gatheringColor) {
                    if($char == "{" && isset($str[$i+1]) && $str[$i+1] == "{") {
                        $gatheringColor = true;
                    } else {
                        echo $char;
                    }
                } else {
                    $buffer .= $char;
                    if(substr($buffer, -2) == "}}") {
                        $color = str_replace("{", "", str_replace("}", "", $buffer));
                        $color = "COLOR_".strtoupper($color);

                        $buffer = "";
                        $gatheringColor = false;

                        if(defined($color) || $color == "COLOR_DEFAULT") {
                            Console::generateColor($color == "COLOR_DEFAULT" ? Console::$defaultColor : constant($color));
                        } else {
                            Console::generateColor(COLOR_RED);
                            echo "[ERROR: Color not found]";
                            Console::generateColor(Console::$defaultColor);
                        }
                    }
                }
            }
        }

        public static function error($str, $die = true, $newLine = true) {
            Console::write("{{red}}".($die ? "FATAL " : "")."ERROR{{default}}: $str", $newLine);
            if($die) exit;
        }

    }

?>