<?php

    namespace ZubZet\Framework\Message;

    use ZubZet\Framework\ZubZet;
    use ZubZet\Framework\Core\CanRetrieveModel;
    use ZubZet\Framework\Core\CanRetrieveBooterSettings;

    /**
     * Base class for the response and request objects
     */
    class RequestResponseHandler {

        use CanRetrieveModel;
        use CanRetrieveBooterSettings;

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
         * Returns the framework root directory
         * @return string
         */
        public function getZRoot() {
            return $this->booter->z_framework_root; 
        }
    }
?>
