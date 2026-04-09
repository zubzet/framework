<?php

    namespace ZubZet\Framework\Message;

    use ZubZet\Framework\ZubZet;
    use ZubZet\Framework\Core\CanRetrieveModel;

    /**
     * Base class for the response and request objects
     */
    class RequestResponseHandler {

        use CanRetrieveModel;

        /**
         * @var ZubZet $booter The framework object
         */
        public $booter;

        public function __construct() {
            $this->booter = zubzet();
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
         * Gets a booter setting
         * @param string $key Key of the setting
         * @return mixed Value of the key
         */
        public function getBooterSettings($key = null, $useDefault = true, $default = null) {
            // Return all settings if no key is provided
            if(empty($key)) return zubzet()->getAllAttributes();

            // Return specific setting if it exists
            if(isset(zubzet()->{$key})) return zubzet()->{$key};

            // Return default if enabled
            if($useDefault) return $default;

            throw new \InvalidArgumentException("The setting '$key' does not exist!");
        }
    }
?>
