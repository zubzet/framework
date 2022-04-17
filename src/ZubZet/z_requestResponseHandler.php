<?php 
    /**
     * ResponseRequest handler
     */

    /**
     * Base class for the response and request object
     */
    class RequestResponseHandler {

        /**
         * @var $booter The framework object
         */
        public $booter;

        /**
         * Constructor every request and response object should have
         * @param $booter The framework object
         */
        public function __construct($booter) {
            $this->booter = $booter;
        }

        /**
         * Returns the ZViews directory
         * @return String
         */
        public function getZViews() {
            var_dump(getcwd());
            return $this->booter->z_views;
        }

        /**
         * Returns the ZControllers directory
         * @return String
         */
        public function getZControllers() {
            return $this->booter->z_controllers;
        }

        /**
         * Returns the framework root directory
         * @return String
         */
        public function getZRoot() {
            return $this->booter->vendorRoot; 
        }

        /**
         * Gets the database communication stuff
         * @return z_model
         */
        public function getModel() {
            return $this->booter->getModel(...func_get_args());
        }

        /**
         * Gets a booter settings
         * @param String $key Key of the settings
         * @return Any Value of the key
         */
        public function getBooterSettings($key = null, $useDefault = true, $default = null) {
            if(!empty($key)) {
                if(!isset($this->booter->settings[$key])) {
                    if($useDefault) {
                        return $default;
                    }
                    throw new \Exception("The setting '$key' does not exist!");
                }
                return $this->booter->settings[$key];
            }
            return $this->booter->settings;
        }

    }
?>