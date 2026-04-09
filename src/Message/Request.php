<?php

    namespace ZubZet\Framework\Message;

    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Message\Input\State;
    use ZubZet\Framework\Message\Input\CanRetrieveFromInput;
    use ZubZet\Framework\Form\Validation\CanValidateForm;
    use ZubZet\Framework\Form\Validation\CanValidateMultiForm;

    /**
     * Base class for Response and Request
     */
    class Request extends RequestResponseHandler {

        use CanValidateForm;
        use CanValidateMultiForm;
        use CanRetrieveFromInput;

        public function __construct(public State $input) {
            parent::__construct();
        }

        /**
         * @var array Store values within the Request to pass through data within internal redirects
         */
        public array $store = [];

        public array $urlParameters = [];

        public function getRouteParameter($key = null) {
            if(isset($key)) {
                return $this->urlParameters[$key] ?? null;
            }

            return $this->urlParameters;
        }

        public array $urlParts;

        public function getUrlParts(): array {
            if(isset($this->urlParts)) {
                return $this->urlParts;
            }

            // Parse the path
            $path = $this->input->SERVER['REQUEST_URI'] ?? "";
            $path = parse_url($path, PHP_URL_PATH) ?: "";
            $path = trim($path, '/');

            $urlParts = empty($path) ? [] : explode("/", $path);
            if(!empty(config("rootDirectory"))) {
                $rootDirectoryLength = count(explode("/", config("rootDirectory")));
                for($i = 0; $i < $rootDirectoryLength; $i++) {
                    array_shift($urlParts);
                }
            }

            $this->urlParts = $urlParts;
            return $this->urlParts;
        }

        /**
         * Gets the IP of a request
         * @return ?string The IP of the client. False if no IP is detected
         */
        public function ip(): ?string {
            $ip = $this->input->SERVER['HTTP_CLIENT_IP']
                ?? $this->input->SERVER['HTTP_X_FORWARDED_FOR']
                ?? $this->input->SERVER['REMOTE_ADDR']
                ?? getenv('HTTP_CLIENT_IP')
                ?? getenv('HTTP_X_FORWARDED_FOR')
                ?? getenv('HTTP_X_FORWARDED')
                ?? getenv('HTTP_FORWARDED_FOR')
                ?? getenv('HTTP_FORWARDED')
                ?? getenv('REMOTE_ADDR')
                ?? null;
            return $ip;
        }

        /**
         * Detects if a request was made from the console
         *
         * @return bool True if the request was made from a console
         */
        public function isCli(): bool {
            if(defined('STDIN')) {
                return true;
            }

            $remoteAddr = empty($this->input->SERVER['REMOTE_ADDR']);
            $userAgent = isset($this->input->SERVER['HTTP_USER_AGENT']);
            $args = count($this->input->SERVER['argv'] ?? []);

            return $remoteAddr && !$userAgent && $args > 0;
        }

        public function referer(): ?string {
            return $this->input->SERVER['HTTP_REFERER'] ?? null;
        }

        public function userAgent(): ?string {
            return $this->input->SERVER['HTTP_USER_AGENT'] ?? null;
        }

        public function getExecutionTime(): ?float {
            if(!isset($this->input->SERVER["REQUEST_TIME_FLOAT"])) return null;
            return microtime(true) - $this->input->SERVER["REQUEST_TIME_FLOAT"];
        }

        /**
         * Returns the current URL
         * @return string Returns the current URL, consisting of the configured host and the actual current path
         */
        public function getCurrentURL(): string {
            return config("host") . ($this->input->SERVER['REQUEST_URI'] ?? "");
        }

        /**
         * Returns the current root URL including protocol and root directory
         * @return string a URL like $opt["root"] but including the host before
         */
        public function getRoot(): string {
            return config("root");
        }

        /**
         * Returns the app domain as specified in the configuration (`host=`)
         * @return string the domain
         */
        public function getDomain(): string {
            return explode(
                ":",
                str_replace(
                    ["http://", "https://", "/"],
                    "",
                    (string) config("host"),
                ),
            )[0];
        }

        /**
         * Gets the URL parameters (including the leading controller and action) specified by the path.
         * @param int $offset The offset from which to start. Can be -1 if action_fallback is used
         * @param int $length The amount of array elements that will be returned at the set offset. If null, every element will be returned
         * @param string $val If the length is 1, a Boolean will be returned. $val will be compared to the parameter
         * @return mixed[]|string
         */
        public function getParameters($offset = 0, $length = null, $val = null): mixed {
            //Get the current url parts from the booter
            $params = $this->getUrlParts();

            //At least shift two params, because of Controller/Action
            for ($i = 0; $i < 2 + $offset; $i++) array_shift($params);

            //New keys as array_shift does not change them
            $params = array_values($params);

            //Compare with default value
            if ($length == 1 && !isset($params[0])) return false;
            if ($length == 1) return isset($val) ? $params[0] == $val : $params[0];

            //Slice the resulting array according to the length parameter
            return array_slice($params, 0, $length);
        }

        /**
         * Works like getParameters and decodes an SEO optimized URL. Example: test.com/episodes/this-is-some-text-64 The 64 is an id
         * @param int $offset The offset from which to start. Can be -1 if action_fallback is used
         * @return string[] [id, text] of the URL
         */
        public function getReadableParameter($offset = 0): array {
            $param = $this->getParameters($offset, 1);
            $param = explode("-", $param);
            $id = $param[count($param) - 1];
            array_pop($param);
            return [
                "id" => $id,
                "text" => implode("-", $param),
            ];
        }

        /**
         * Gets the user who made the request.
         * @return \User Object of the requesting user
         */
        public function getRequestingUser(): User {
            return user();
        }

        /**
         * Gets the path to the root folder of the project.
         * @return string Path to the root folder
         */
        public function getRootFolder(): string {
            return config("rootFolder");
        }

        public function checkSuperPermission(string $permission, bool $boolResult = false): bool {
            return $this->checkPermission($permission, $boolResult, true);
        }

        /**
         * Checks if the current user has a permission. If the user is not logged in, they will be redirected to the login page. 
         * If the user is logged in but does not have the permission, they will be redirected to 403.
         * @param string $permission Permission to check for
         * @param bool $boolResult If true, the function will return a boolean result instead of redirecting
         */
        public function checkPermission(string $permission, bool $boolResult = false, bool $includeSuperUser = false): bool {
            if($permission == "console") {
                if($this->isCli()) return true;
                if($boolResult) return false;
                zubzet()->executePath(["error", "403"]);
                exit;
            }

            if(!user()->isLoggedIn) {
                if($boolResult) return false;
                zubzet()->executePath(["login", "index"]);
                exit;
            }

            $hasPermission = $includeSuperUser
                ? user()->checkSuperPermission($permission)
                : user()->checkPermission($permission);

            if(!$hasPermission) {
                if($boolResult) return false;
                zubzet()->executePath(["error", "403"]);
                exit;
            }

            return true;
        }

        /**
         * Checks if the request contains form data. When it contains form data, methods like validateForm() can be used.
         * @return bool
         */
        public function hasFormData(): bool {
            return isset($this->input->POST["isFormData"]);
        }

        /**
         * Checks if the request is an async AJAX request of a given type
         * @param string $type Type of the request. Request is sent via Z.js => Z.Request.action()
         * @return bool True if the request is of the specified type
         */
        public function isAction(string $type): bool {
            return $this->getPost("action") == $type;
        }

        /**
         * Get the body of the request
         *
         * Reads the raw input from the request body.
         *
         * @return ?string The raw body content of the request (Returns an empty string if empty or unavailable).
         */
        public function getBody(): ?string {
            return $this->input->body;
        }

        /**
         * Decode request body as JSON.
         *
         * This method decodes the raw request body as JSON and returns the resulting value.
         * Objects and arrays are returned as associative arrays.
         *
         * @return mixed The decoded JSON value, or null if the body is empty.
         * @throws \JsonException If the body contains invalid JSON.
         */
        public function getJson(): mixed {
            $data = $this->getBody();
            if(is_null($data)) return null;
            return json_decode($data, true, flags: JSON_THROW_ON_ERROR);
        }
    }
?>
