<?php
    class Rest {

        private $data;
        private $json;

        function __construct($data, $urlParts) {
            $this->data = [
                'meta' => [
                    "endpoint" => "Your Websites REST API",
                    "request" => implode("/", $urlParts),
                    "timestamp" => time()
                ]
            ];
            foreach ($data as $key => $val) {
                $this->data[$key] = $val;
            }
        }

        public function ShowError($code, $message) {
            $this->data = [
                'error' => [
                    'code' => $code,
                    'message' => $message
                ]
            ];
            $this->execute();
        }

        public function execute($die = true) {
            $this->json = json_encode($this->data, JSON_PRETTY_PRINT);
            echo $this->json;
            if($die) exit;
        }

    }
?>