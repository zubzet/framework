<?php

    namespace ZubZet\Framework\Message\Input;

    trait CanRetrieveFromInput {
        /**
         * Gets a GET parameter
         * @param string $key The key of the parameter
         * @param mixed $default Default value
         * @return string|mixed The content of the GET value
         */
        public function getGet(?string $key = null, mixed $default = null): mixed {
            if(is_null($key)) {
                return $this->input->GET;
            }
            if(isset($this->input->GET[$key])) {
                return $this->input->GET[$key];
            }
            return $default;
        }

        /**
         * Gets a POST parameter
         * @param ?string $key The key of the parameter
         * @param mixed $default Default value
         * @return string|mixed The content of the POST value
         */
        public function getPost(?string $key = null, mixed $default = null): mixed {
            if(is_null($key)) {
                return $this->input->POST;
            }
            if(isset($this->input->POST[$key])) {
                return $this->input->POST[$key];
            }
            return $default;
        }

        /**
         * Gets a posted file
         * @param ?string $key The name of the file
         * @param mixed $default Default value if the file is not posted
         * @return mixed The posted file
         */
        public function getFile(?string $key = null, mixed $default = null): mixed {
            if(is_null($key)) {
                return $this->input->FILES;
            }
            if(isset($this->input->FILES[$key])) {
                return $this->input->FILES[$key];
            }
            return $default;
        }

        /**
         * Alias for getFile() to get all posted files
         * @return array The posted files
         */
        public function getFiles(): array {
            return $this->getFile();
        }

        /**
         * Gets a cookie
         * @param string $key The key of the parameter
         * @param mixed $default Default value
         * @return mixed Content of the cookie
         */
        public function getCookie(?string $key = null, mixed $default = null): mixed {
            if(is_null($key)) {
                return $this->input->COOKIE;
            }
            if(isset($this->input->COOKIE[$key])) {
                return $this->input->COOKIE[$key];
            }
            return $default;
        }

        /**
         * Alias for getCookie() to get all cookies
         * @return array The cookies
         */
        public function getCookies(): array {
            return $this->getCookie();
        }
    }

?>