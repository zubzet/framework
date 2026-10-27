<?php

    namespace ZubZet\Framework\Support;

    /**
     * The rest class is used to provide raw JSON API data to the client
     */
    class Rest {

        /**
         * @var array $data Payload of the rest
         */
        private $data;

        /**
         * @var string $json The payload formatted as json
         */
        private $json;

        /**
         * Creates a rest object
         * @param array $data Payload to send; merged after the auto-generated 'meta' block (endpoint, request, timestamp), so a 'meta' key in it overwrites that block.
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
         * Replaces the payload with an error object, sends it to the client and exits via execute()
         * @param string|int $code The error code
         * @param string $message A human readable message, explaining the error
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
         * Echoes the payload as pretty-printed JSON (no Content-Type header is sent)
         * @param bool $die Whether to call exit after echoing
         */
        public function execute($die = true) {
            $this->json = json_encode($this->data, JSON_PRETTY_PRINT);
            echo $this->json;
            if($die) exit;
        }

    }
?>