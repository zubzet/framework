<?php
    /**
     * This file holds the rest class
     */

    /**
     * The rest class is used to provide raw JSON API data to the client
     */
    class Rest {

        /**
         * @var object $data Payload of the rest
         */
        private $data;

        /**
         * @var string $json The payload formated as json
         */
        private $json;

        /**
         * Creates a rest object
         * @param object $data Data should be send.
         * @param string[] $urlParts to which the request was targeted at
         */
        function __construct($data, $urlParts) {
            $this->data = [
                'meta' => [
                    "endpoint" => "REST API",
                    "request" => implode("/", $urlParts),
                    "timestamp" => time()
                ]
            ];
            foreach ($data as $key => $val) {
                $this->data[$key] = $val;
            }
        }

        /**
         * Sends an error to the client
         * @param string $code The error code
         * @param string $message A human readable message, explaning the error
         */
        public function ShowError($code, $message) {
            $this->data = [
                'error' => [
                    'code' => $code,
                    'message' => $message
                ]
            ];
            $this->execute();
        }

        /**
         * Sends the rest stuff to the client
         * @param bool $die Should the program end after sending?
         */
        public function execute($die = true) {
            $this->json = json_encode($this->data, JSON_PRETTY_PRINT);
            echo $this->json;
            if($die) exit;
        }

    }
?>