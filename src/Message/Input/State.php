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

        // The properties fromOverwrite() can merge into, body and previous hold no array
        private const OVERWRITABLE = ["SERVER", "GET", "POST", "FILES", "REQUEST", "SESSION", "COOKIE"];

        public static function fromRequest(): State {
            $input = new self();

            // Handle standard globals
            $input->SERVER = $_SERVER ?? [];
            $input->GET = $_GET ?? [];
            $input->FILES = $_FILES ?? [];
            $input->REQUEST = $_REQUEST ?? [];
            $rawPost = $_POST ?? [];
            $input->SESSION = $_SESSION ?? [];
            $input->COOKIE = $_COOKIE ?? [];

            // mod_php hands the Authorization header to getallheaders() only, not to $_SERVER
            if(!isset($input->SERVER['HTTP_AUTHORIZATION']) && function_exists('getallheaders')) {
                $headers = array_change_key_case(getallheaders(), CASE_LOWER);
                if(isset($headers['authorization'])) $input->SERVER['HTTP_AUTHORIZATION'] = $headers['authorization'];
            }

            // Parse Post request
            array_walk_recursive($_POST, function(&$item) {
                if(substr($item, 0, 10) == "<#decURI#>") {
                    $item = substr($item, 10);
                    $item = rawurldecode($item);
                }
            });
            $input->POST = $_POST ?? [];

            // The request holds the values of the post as they were sent, so they are replaced by the decoded ones
            foreach($rawPost as $key => $rawValue) {
                if(!array_key_exists($key, $input->REQUEST)) continue;
                if($input->REQUEST[$key] !== $rawValue) continue;
                $input->REQUEST[$key] = $input->POST[$key];
            }

            // Handle POST body
            $body = file_get_contents('php://input');
            $input->body = $body ?: null;

            return $input;
        }

        public static function fromOverwrite(State $input, array $overwriteData = []): State {
            $newInput = clone $input;
            $newInput->previous = &$input;

            foreach($overwriteData as $type => $values) {
                if(!in_array($type, self::OVERWRITABLE, true)) {
                    throw new \InvalidArgumentException("OverwriteData includes unknown type: '$type'.");
                }
                foreach($values as $key => $value) {
                    $newInput->{$type}[$key] = $value;
                }
            }

            // The request follows its sources, an explicitly given one is applied on top
            if(!empty(array_intersect_key($overwriteData, ["GET" => 1, "POST" => 1, "COOKIE" => 1]))) {
                $newInput->updateRequest();
                foreach($overwriteData["REQUEST"] ?? [] as $key => $value) {
                    $newInput->REQUEST[$key] = $value;
                }
            }

            return $newInput;
        }

        public function withUrl(string $url): State {
            $url = parse_url($url);

            if(isset($url["scheme"])) {
                // A scheme is case insensitive
                $scheme = strtolower($url["scheme"]);
                $this->SERVER["REQUEST_SCHEME"] = $scheme;
                $this->SERVER["HTTPS"] = "https" === $scheme ? "on" : "off";
            }

            if(isset($url["host"])) {
                // Like the Host header, which carries a port that is not the default one
                $port = isset($url["port"]) ? ":" . $url["port"] : "";
                $this->SERVER["HTTP_HOST"] = $url["host"] . $port;
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
            $hasQuery = "" !== (string) ($this->SERVER["QUERY_STRING"] ?? "");
            $query = $hasQuery ? "?" . $this->SERVER["QUERY_STRING"] : "";
            $this->SERVER["REQUEST_URI"] = "/{$path}$query";
            $this->SERVER["REDIRECT_URL"] = "/{$path}";
            return $this;
        }

        public function withGet(array $get = []): State {
            $query = http_build_query($get);
            $this->SERVER["QUERY_STRING"] = $query;

            $currentUri = strtok($this->SERVER["REQUEST_URI"] ?? "", '?');
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

            if(empty($this->previous->SERVER["REQUEST_SCHEME"])) {
                throw new \LogicException("Cannot set previous input as referer when previous input does not have REQUEST_SCHEME set.");
            }

            $oldServer = $this->previous->SERVER;
            $referer = "$oldServer[REQUEST_SCHEME]://$oldServer[HTTP_HOST]";

            if(!empty($oldServer["REQUEST_URI"])) {
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

        public function withArgs(array $args): State {
            $this->SERVER["argv"] = array_merge(
                array_slice($this->SERVER["argv"] ?? [], 0, 2),
                $args,
            );
            return $this;
        }

        private function updateRequest() {
            // Not array_merge(), which renumbers numeric keys
            $this->REQUEST = array_replace(
                $this->GET,
                $this->POST,
                $this->COOKIE,
            );
        }
    }

?>