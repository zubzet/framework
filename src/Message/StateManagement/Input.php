<?php

    namespace ZubZet\Framework\Message\StateManagement;

    class Input {

        public ?Input $previous = null;

        public ?string $body;
        public array $SERVER;
        public array $GET;
        public array $POST;
        public array $FILES;
        public array $REQUEST;
        public array $SESSION;
        public array $COOKIE;

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

        public static function fromOverwrite(Input $input, array $overwriteData = []): Input {
            $newInput = clone $input;
            $newInput->previous = &$input;

            foreach($overwriteData as $type => $values) {
                if(!property_exists($newInput, $type)) {
                    throw new \InvalidArgumentException("OverwriteData includes unknown type: '$type'.");
                }
                foreach($values as $key => $value) {
                    $newInput->{$type}[$key] = $value;
                }
            }

            return $newInput;
        }

        public function withUrl(string $url): Input {
            $url = parse_url($url);

            if(isset($url["scheme"])) {
                $this->SERVER["REQUEST_SCHEME"] = $url["scheme"];
                $this->SERVER["HTTPS"] = "https" === $url["scheme"] ? "on" : "off";
            }

            if(isset($url["host"])) {
                $this->SERVER["HTTP_HOST"] = $url["host"];
            }

            if(isset($url["query"])) {
                $get = [];
                parse_str($url["query"], $get);
                $this->withGet($get);
            }

            if(isset($url["path"])) {
                $this->withPath($url["path"]);
            }

            return $this;
        }

        public function withPath(string $path): Input {
            $path = ltrim($path, "/");
            $hasQuery = !empty($this->SERVER["QUERY_STRING"]);
            $query = $hasQuery ? "?" . $this->SERVER["QUERY_STRING"] : "";
            $this->SERVER["REQUEST_URI"] = "/{$path}$query";
            $this->SERVER["REDIRECT_URL"] = "/{$path}";
            return $this;
        }

        public function withGet(array $get = []): Input {
            $query = http_build_query($get);
            $this->SERVER["QUERY_STRING"] = $query;

            $currentUri = strtok($this->SERVER["REQUEST_URI"], '?');
            $this->SERVER["REQUEST_URI"] = $currentUri . (!empty($get) ? "?$query" : "");

            $this->GET = $get;
            return $this;
        }

        public function withBody(string $body): Input {
            $this->body = $body;
            return $this;
        }

        public function withPost(array $post = []): Input {
            $this->POST = $post;
            return $this;
        }

        public function withFiles(array $files = []): Input {
            $this->FILES = $files;
            return $this;
        }

        public function withSession(array $session = []): Input {
            $this->SESSION = $session;
            return $this;
        }

        public function withCookies(array $cookie = []): Input {
            $this->COOKIE = $cookie;
            return $this;
        }

        public function withMethod(string $method): Input {
            $this->SERVER["REQUEST_METHOD"] = $method;
            return $this;
        }

        public function withPreviousAsReferer(): Input {
            if(!$this->previous) {
                throw new \LogicException("Cannot set previous input as referer when there is no previous input.");
            }

            if(empty($this->previous->SERVER["HTTP_HOST"])) {
                throw new \LogicException("Cannot set previous input as referer when previous input does not have HTTP_HOST set.");
            }

            $oldServer = $this->previous->SERVER;
            $referer = "";

            if(!empty($oldServer["REQUEST_SCHEME"]) && !empty($oldServer["HTTP_HOST"])) {
                $referer .= "$oldServer[REQUEST_SCHEME]://$oldServer[HTTP_HOST]";
            }

            if($oldServer["REQUEST_URI"]) {
                $path = ltrim($oldServer["REQUEST_URI"], "/");
                $referer .= "/$path";
            }

            $this->SERVER["HTTP_REFERER"] = $referer;
            return $this;
        }

        public function withReferer(string $referer): Input {
            $this->SERVER["HTTP_REFERER"] = $referer;
            return $this;
        }
    }

?>