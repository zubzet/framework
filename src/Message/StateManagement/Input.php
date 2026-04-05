<?php

    namespace ZubZet\Framework\Message\StateManagement;

    class Input {

        public ?string $body;
        public array $SERVER;
        public array $GET;
        public array $POST;
        public array $FILES;
        public array $REQUEST;
        public array $SESSION;
        public array $COOKIE;

        public static function fromOverwrite(Input $input, array $overwriteData): Input {
            $newInput = clone $input;

            foreach($overwriteData as $key => $value) {
                if(!property_exists($newInput, $key)) {
                    throw new \InvalidArgumentException("OverwriteData includes unknown key: '$key'.");
                }
                $newInput->{$key} = $value;
            }

            return $newInput;
        }

        public static function fromRequest(): Input {
            $input = new self();

            // Handle standard globals
            $input->SERVER = $_SERVER ?? [];
            $input->GET = $_GET ?? [];
            $input->FILES = $_FILES ?? [];
            $input->REQUEST = $_REQUEST ?? [];
            $input->SESSION = $_SESSION ?? [];
            $input->COOKIE = $_COOKIE ?? [];

            // Parse Post request
            array_walk_recursive($_POST, function(&$item) {
                if(substr($item, 0, 10) == "<#decb64#>") {
                    $item = substr($item, 10);
                    $item = base64_decode($item);
                }
                if(substr($item, 0, 10) == "<#decURI#>") {
                    $item = substr($item, 10);
                    $item = rawurldecode($item);
                }
            });
            $input->POST = $_POST ?? [];

            // Handle POST body
            $body = file_get_contents('php://input');
            $input->body = $body ?: null;

            return $input;
        }


    }

?>