<?php 
    class RequestResponseHandler {

        public $booter;
        protected $z_views;

        public function __construct($booter) {
            $this->booter = $booter;
        }

        public function getZViews() {
            return $this->booter->z_views;
        }

        public function getZRoot() {
            return $this->booter->z_framework_root; 
        }

    }
?>