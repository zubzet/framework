<?php

    namespace ZubZet\Framework\Message\Input;

    class State {

        public ?State $previous = null;

        public ?string $body;
        public array $SERVER;
        public array $GET;
        public array $POST;
        public array $FILES;
        public array $REQUEST;
        public array $SESSION;
        public array $COOKIE;

        public static function fromRequest(): State {
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

        public static function fromOverwrite(State $input, array $overwriteData = []): State {
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

        public function withUrl(string $url): State {
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

        public function withPath(string $path): State {
            $path = ltrim($path, "/");
            $hasQuery = !empty($this->SERVER["QUERY_STRING"]);
            $query = $hasQuery ? "?" . $this->SERVER["QUERY_STRING"] : "";
            $this->SERVER["REQUEST_URI"] = "/{$path}$query";
            $this->SERVER["REDIRECT_URL"] = "/{$path}";
            return $this;
        }

        public function withGet(array $get = []): State {
            $query = http_build_query($get);
            $this->SERVER["QUERY_STRING"] = $query;

            $currentUri = strtok($this->SERVER["REQUEST_URI"], '?');
            $this->SERVER["REQUEST_URI"] = $currentUri . (!empty($get) ? "?$query" : "");

            $this->GET = $get;
            $this->updateRequest();
            return $this;
        }

        public function withBody(string $body): State {
            $this->body = $body;
            return $this;
        }

        public function withPost(array $post = []): State {
            $this->POST = $post;
            $this->updateRequest();
            return $this;
        }

        public function withFiles(array $files = []): State {
            $this->FILES = $files;
            return $this;
        }

        public function withSession(array $session = []): State {
            $this->SESSION = $session;
            return $this;
        }

        public function withCookies(array $cookie = []): State {
            $this->COOKIE = $cookie;
            $this->updateRequest();
            return $this;
        }

        public function withMethod(string $method): State {
            $this->SERVER["REQUEST_METHOD"] = $method;
            return $this;
        }

        public function withPreviousAsReferer(): State {
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

        public function withReferer(string $referer): State {
            $this->SERVER["HTTP_REFERER"] = $referer;
            return $this;
        }

        private function updateRequest() {
            $this->REQUEST = array_merge(
                $this->GET,
                $this->POST,
                $this->COOKIE,
            );
        }
    }

?>