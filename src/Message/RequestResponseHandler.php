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
         * Ends the request here: nothing of the action runs after this call. What was printed and
         * every header that was set stay as they are, and the afterMiddlewares of the route still run.
         * Takes the place of `exit` and `die`, see EndOfRequest for how the request ends.
         * @return never
         */
        public function stop(): void {
            throw new EndOfRequest;
        }

        /**
         * Returns the project's own views directory (config z_views, relative path with trailing slash), not the framework's IncludedComponents views
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
