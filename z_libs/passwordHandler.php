<?php
    /** 
     * passwordHandler
     * @author (ALZlper) Alexander Zierhut 
     * Web: alzlper.com
     */

    /**
     * Password handling class
     */
    class passwordHandler {
        
        /**
         * @var string String with all possible pepper chars
         */
        private static $charUniverse = "abcdefghijklmnopqrstuvwxyz";
        
        /**
         * Sets a custom string for pepper chars. This method allows to create custom pepper sets.
         * @param string $charUniverseParam A string containing new all possible pepper chars
         */
        public static function setCharUniverse($charUniverseParam) {
            self::$charUniverse = $charUniverseParam;
        }
        
        /**
         * Generates a salt
         * @return string A salt
         */
        private static function generateSalt() {
            return uniqid(mt_rand(), true);
        }
        
        //Pepper generator using $charUniverse
        /**
         * Generates pepper using the set charUniverse
         * @return string Pepper
         */
        private static function generatePepper() {
            $randCharID = rand(0, strlen(self::$charUniverse) - 1);
            return self::$charUniverse[$randCharID];
        }
        
        /**
         * Hashes a string with sha512
         * @param string $str The input string
         * @return string The hashed version of the input string
         */
        private static function hashStr($str) {
            return hash('sha512', $str);
        }
        
        /**
         * Custom spagetti logic
         */
        private static function customAlg($str) {
            $str .= str_repeat(substr($str, 2), 2);
            $str = strrev($str);
            $str = self::customAlgEnc($str);
            return $str;
        }
        
        /**
         * Encryption function: uses str as key
         * @param string $string The key
         */
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
        
        /**
         * Processes a password
         * @param string $str The raw password
         * @return string A finished hashed, salted and peppered password to save somewhere
         */
        private static function processStr($str) {
            return self::hashStr(self::customAlg($str));
        }
        
        /**
         * Function to generate all Pepper options
         * @param string $userInput The raw password
         * @param string $salt A salt
         * @return string[] possible options
         */
        private static function generateCheckOptions($userInput, $salt) {
            $passwordOptions = array();
            for ($i = 0; $i < strlen(self::$charUniverse); $i++) {
                array_push($passwordOptions, self::processStr($userInput.$salt.self::$charUniverse[$i]));
            }
            return $passwordOptions;
        }

        /**
         * Creates a password object out of a raw password
         * @param string $userInput The raw password
         * @return array The generated password object
         */
        public static function createPassword($userInput) {
            if (empty($userInput)) throw new Exception('Please specify the userInput parameter');
            if (strlen($userInput) < 3) throw new Exception('A password must at least have 3 chars');
            $salt = self::generateSalt();
            return array("hash" => self::processStr(base64_encode($userInput) . $salt . self::generatePepper()), "salt" => $salt);
        }
        
        /**
         * Checks if a password belongs to a hashed version
         * @param string $userInput The raw password
         * @param string $hash The hash to check
         * @param string $salt The used salt
         * @return bool True when password matches
         */
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