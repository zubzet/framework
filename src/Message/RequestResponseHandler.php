<?php

    namespace ZubZet\Framework\Message;

    use ZubZet\Framework\Core\Model;
    use ZubZet\Framework\ZubZet;

    /**
     * Base class for the response and request objects
     */
    class RequestResponseHandler {

        /**
         * @var ZubZet $booter The framework object
         */
        public $booter;

        /**
         * Constructor that every request and response object should have
         * @param ZubZet $booter The framework object
         */
        public function __construct($booter) {
            $this->booter = $booter;
        }

        /**
         * Returns the ZViews directory
         * @return string
         */
        public function getZViews() {
            return $this->booter->z_views;
        }

        /**
         * Returns the ZControllers directory
         * @return string
         */
        public function getZControllers() {
            return $this->booter->z_controllers;
        }

        /**
         * Returns the framework root directory
         * @return string
         */
        public function getZRoot() {
            return $this->booter->z_framework_root; 
        }

        /**
         * Gets the database communication interface
         * @return Model
         */
        public function getModel() {
            return model(...func_get_args());
        }

        /**
         * Gets a booter setting
         * @param string $key Key of the setting
         * @return mixed Value of the key
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
