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
                // Arrays never arrive here: array_walk_recursive() walks into them and only hands over
                // their leaves. A leaf that is no text (null, a number) comes from an application that
                // filled $_POST itself, from a JSON body for example, and has no prefix to decode.
                if(!is_string($item)) return;

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
            // Only a missing or empty body is null, "0" is a body
            $body = (string) file_get_contents('php://input');
            $input->body = "" === $body ? null : $body;

            return $input;
        }

        public static function fromOverwrite(State $input, array $overwriteData = []): State {
            $newInput = clone $input;
            $newInput->previous = &$input;

            foreach($overwriteData as $type => $values) {
                if(!in_array($type, self::OVERWRITABLE, true)) {
                    throw new \InvalidArgumentException("OverwriteData includes unknown type: '$type'.");
                }
                if(!is_array($values)) {
                    throw new \InvalidArgumentException("OverwriteData for '$type' must be an array.");
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

        /**
         * Points the state to a url, resolved against the current one like a
         * browser resolves a link (RFC 3986, section 5.2):
         *
         * - a url with a scheme or a host replaces path and query, without a path it points to the start page
         * - a path replaces the path and drops the query, unless the url brings a new one
         * - a path without a leading slash is relative to the current one, `.` and `..` are resolved
         * - a query alone replaces the query and keeps the path
         *
         * To change the path and keep the query, use withPath().
         *
         * @throws \InvalidArgumentException For a url that cannot be read
         */
        public function withUrl(string $url): State {
            $url = self::splitUrl($url);

            if(isset($url["scheme"])) {
                // A scheme is case insensitive
                $scheme = strtolower($url["scheme"]);
                $this->SERVER["REQUEST_SCHEME"] = $scheme;

                // PHP reads any non empty value as https, so there is none for another scheme
                unset($this->SERVER["HTTPS"]);
                if("https" === $scheme) $this->SERVER["HTTPS"] = "on";
            }

            if(isset($url["host"])) {
                // Like the Host header, which carries a port that is not the default one
                $port = isset($url["port"]) ? ":" . $url["port"] : "";
                $this->SERVER["HTTP_HOST"] = $url["host"] . $port;
            }

            $path = $url["path"] ?? "";
            $namesAHost = isset($url["scheme"]) || isset($url["host"]);

            // Only a query, a fragment or nothing: the path stays, and so does the query when none is given
            if("" === $path && !$namesAHost) {
                if(isset($url["query"])) $this->writeQuery($url["query"]);
                return $this;
            }

            // A path of its own starts at the root or, without a leading slash, next to the current one
            if(!str_starts_with($path, "/") && !$namesAHost) {
                $current = explode("?", $this->SERVER["REQUEST_URI"] ?? "", 2)[0];
                $path = substr($current, 0, (int) strrpos($current, "/")) . "/" . $path;
            }

            // The query belongs to the path it came with, so the old one goes
            $this->writeQuery($url["query"] ?? "");

            return $this->withPath(self::removeDotSegments("/" . ltrim($path, "/")));
        }

        // RFC 3986, section 5.2.4: "." names the folder itself and ".." the one above
        private static function removeDotSegments(string $path): string {
            $segments = explode("/", $path);
            $last = array_key_last($segments);
            $kept = [];

            foreach($segments as $index => $segment) {
                $isDot = "." === $segment;
                $isDoubleDot = ".." === $segment;

                // Never above the root, which is the empty segment in front of the first slash
                if($isDoubleDot && count($kept) > 1) array_pop($kept);

                if(!$isDot && !$isDoubleDot) $kept[] = $segment;

                // A folder at the end keeps its slash
                if(($isDot || $isDoubleDot) && $index === $last) $kept[] = "";
            }

            return implode("/", $kept);
        }

        // Sets GET like PHP decodes its own from the query string, which itself stays as it was given
        private function writeQuery(string $query): void {
            $get = [];
            parse_str($query, $get);

            $this->GET = $get;
            $this->updateRequest();
            $this->writeQueryString($query);
        }

        /** @return array<string, string|int> The parts of the url as parse_url() names them */
        private static function splitUrl(string $url): array {
            // parse_url() takes the ":1" of a path like "/x:1" for a port and gives up, so a path is cut by hand
            if(str_starts_with($url, "/") && !str_starts_with($url, "//")) {
                $withoutFragment = explode("#", $url, 2)[0];
                $pathAndQuery = explode("?", $withoutFragment, 2);

                return array_filter(
                    ["path" => $pathAndQuery[0], "query" => $pathAndQuery[1] ?? null],
                    fn($part) => null !== $part,
                );
            }

            $parts = parse_url($url);
            if(false === $parts) {
                throw new \InvalidArgumentException("Cannot read the url '$url'.");
            }

            return $parts;
        }

        /**
         * Replaces the path and nothing else: the query stays. This is what an
         * internal reroute uses, the action it reaches still sees the query of
         * the request. withUrl() drops the query like a link would.
         */
        public function withPath(string $path): State {
            $path = ltrim($path, "/");
            $hasQuery = "" !== (string) ($this->SERVER["QUERY_STRING"] ?? "");
            $query = $hasQuery ? "?" . $this->SERVER["QUERY_STRING"] : "";
            $this->SERVER["REQUEST_URI"] = "/{$path}$query";
            $this->SERVER["REDIRECT_URL"] = "/{$path}";
            return $this;
        }

        public function withGet(array $get = []): State {
            $this->GET = $get;
            $this->updateRequest();
            $this->writeQueryString(http_build_query($get));
            return $this;
        }

        // Sets the query string and replaces the one of the request uri with it
        private function writeQueryString(string $query): void {
            $this->SERVER["QUERY_STRING"] = $query;

            // Not strtok(): it skips a leading "?" and keeps one position for the whole process
            $path = explode("?", $this->SERVER["REQUEST_URI"] ?? "", 2)[0];
            $this->SERVER["REQUEST_URI"] = $path . ("" !== $query ? "?$query" : "");
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
            $sources = ["G" => $this->GET, "P" => $this->POST, "C" => $this->COOKIE];

            // Not array_merge(), which renumbers numeric keys
            $this->REQUEST = [];
            foreach(str_split(strtoupper($this->requestOrder())) as $source) {
                $this->REQUEST = array_replace($this->REQUEST, $sources[$source] ?? []);
            }
        }

        /**
         * Which of GET, POST and COOKIE make up REQUEST, as letters in the order
         * they are applied, so a later one wins. PHP fills $_REQUEST by its
         * request_order setting; php.ini-production ships "GP", which keeps the
         * cookies out.
         */
        protected function requestOrder(): string {
            $order = (string) ini_get("request_order");

            // Measured: PHP falls back to variables_order only when request_order is not set at all.
            // Set to nothing it names no source and $_REQUEST stays empty. ini_get() reports both
            // as "", the configuration file tells them apart.
            $isUnset = "" === $order && false === get_cfg_var("request_order");
            if($isUnset) return (string) ini_get("variables_order");

            return $order;
        }
    }

?>