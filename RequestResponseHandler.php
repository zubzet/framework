<?php 
    /**
     * ResponseRequest handler
     */

    /**
     * Base class for the response and request object
     */
    class RequestResponseHandler {

        /**
         * @var ZubZet $booter The framework object
         */
        public ZubZet $booter;

        /**
         * Constructor every request and response object should have
         * @param ZubZet $booter The framework object
         */
        public function __construct(ZubZet $booter) {
            $this->booter = $booter;
        }

        /**
         * Returns the ZViews directory
         * @return String
         */
        public function getZViews() {
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
            return $this->booter->zubzet_root; 
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
        public function getBooterSettings($key = null) {
            if(!empty($key)) {
                if(!isset($this->booter->settings[$key])) {
                    throw new \Exception("The setting '$key' does not exist!");
                }
                return $this->booter->settings[$key];
            }
            return $this->booter->settings;
        }

    }
?>