<?php
    //passwordHandler by (ALZlper) Alexander Zierhut
    //Web: alzlper.com
    class passwordHandler {
        
        //Pepper possibilities
        private static $charUniverse = "abcdefghijklmnopqrstuvwxyz";
        
        //Set a custom charrUniverse
        public static function setCharUniverse($charUniverseParam) {
            self::$charUniverse = $charUniverseParam;
        }
        
        //Salt generator
        private static function generateSalt() {
            return uniqid(mt_rand(), true);
        }
        
        //Pepper generator using $charUniverse
        private static function generatePepper() {
            $randCharID = rand(0, strlen(self::$charUniverse) - 1);
            return self::$charUniverse[$randCharID];
        }
        
        //sha512 str function
        private static function hashStr($str) {
            return hash('sha512', $str);
        }
        
        //Custom spagetti logic
        private static function customAlg($str) {
            $str .= str_repeat(substr($str, 2), 2);
            $str = strrev($str);
            $str = self::customAlgEnc($str);
            return $str;
        }
        
        //Encryption function: uses str as key
        private static function customAlgEnc($string) {
            $key = $string;
            $result = '';
            for($i=0; $i<strlen ($string); $i++) {
                $char = substr($string, $i, 1);
                $keychar = substr($key, ($i % strlen($key))-1, 1);
                $char = chr(ord($char)+ord($keychar));
                $result .= $char;
            }
            return base64_encode($result);
        }
        
        //Function to process a password using hash and custom logic
        private static function processStr($str) {
            return self::hashStr(self::customAlg($str));
        }
        
        //Function to generate all Pepper options
        private static function generateCheckOptions($userInput, $salt) {
            $passwordOptions = array();
            for ($i = 0; $i < strlen(self::$charUniverse); $i++) {
                array_push($passwordOptions, self::processStr($userInput.$salt.self::$charUniverse[$i]));
            }
            return $passwordOptions;
        }
        
        //PasswordHandler to generate a new hash and salt for a password
        public static function createPassword($userInput) {
            if (empty($userInput)) throw new Exception('Please specify the userInput parameter');
            if (strlen($userInput) < 3) throw new Exception('A password must at least have 3 chars');
            $salt = self::generateSalt();
            return array("hash" => self::processStr(base64_encode($userInput) . $salt . self::generatePepper()), "salt" => $salt);
        }
        
        //PasswordHandler to check if a password is correct
        public static function checkPassword($userInput, $hash, $salt) {
            if (empty($userInput)) throw new Exception('Please specify the userInput parameter');
            if (empty($hash)) throw new Exception('Please specify the hash parameter');
            if (empty($salt)) throw new Exception('Please specify the salt parameter');
            if (strlen($userInput) < 3) return false;
            $pwInputOptions = self::generateCheckOptions(base64_encode($userInput), $salt);
            return in_array($hash, $pwInputOptions);
        }
        
    }
?>