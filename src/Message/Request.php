<?php

    namespace ZubZet\Framework\Message;

    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Message\Input\State;
    use ZubZet\Framework\Message\Input\CanRetrieveFromInput;
    use ZubZet\Framework\Form\Validation\CanValidateForm;
    use ZubZet\Framework\Form\Validation\CanValidateMultiForm;
    use ZubZet\Framework\Security\CSRF;

    /**
     * The incoming request: input, URL parts, permission checks and form helpers
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

            // Cut the query like the router does. REQUEST_URI is a path, not a
            // URL: parse_url() reads "/x:1" as host and port and returns false
            $path = $this->input->SERVER['REQUEST_URI'] ?? "";
            $path = explode("?", $path, 2)[0];
            $path = trim($path, '/');

            $urlParts = empty($path) ? [] : explode("/", $path);
            // Surrounding slashes are no segments, "sub/" strips one
            $rootDirectory = trim((string) config("rootDirectory"), '\/');
            if("" !== $rootDirectory) {
                $rootDirectoryLength = count(explode("/", $rootDirectory));
                for($i = 0; $i < $rootDirectoryLength; $i++) {
                    array_shift($urlParts);
                }
                // A doubled slash after the root (root . "/x") is no segment, as in the Router
                while("" === ($urlParts[0] ?? null)) array_shift($urlParts);
            }

            $this->urlParts = $urlParts;
            return $this->urlParts;
        }

        /**
         * Gets the IP of a request
         * @return ?string The IP of the client, client-supplied headers (spoofable) are trusted first. Empty string if none is found
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
            return isCli();
        }

        public function referer(): ?string {
            return $this->input->SERVER['HTTP_REFERER'] ?? null;
        }

        public function userAgent(): ?string {
            return $this->input->SERVER['HTTP_USER_AGENT'] ?? null;
        }

        // The token of an `Authorization: Bearer <token>` header, null for any other scheme
        public function bearerToken(): ?string {
            $header = $this->input->SERVER['HTTP_AUTHORIZATION'] ?? null;

            // "Bearer" in any case, whitespace, then a single token with no spaces in it
            if(!is_string($header) || !preg_match('/^Bearer\s+(\S+)$/i', trim($header), $match)) return null;

            return $match[1];
        }

        public function acceptLanguage(): array {
            $header = $this->input->SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
            if(!is_string($header) || "" === trim($header)) return [];

            // Cap the entries, because the header is client controlled
            $entries = array_slice(explode(",", $header), 0, 50);

            $accepted = [];

            foreach($entries as $entry) {
                // Split locale from optional parameters such as q=0.9
                $parts = array_map("trim", explode(";", $entry));

                $locale = array_shift($parts);
                $quality = 1.0;

                // Only well formed language tags: de, en-US, zh-Hans-CN, es-419
                if(!preg_match('/^[a-z]{1,8}(?:-[a-z0-9]{1,8})*$/i', $locale)) continue;

                // Read the optional quality value; omitted q values default to 1.0.
                foreach($parts as $parameter) {
                    if(!preg_match('/^q\s*=\s*(.*)$/i', $parameter, $match)) continue;

                    // Valid is 0 to 0.999 or 1 followed by zeros. A malformed q must not
                    // fall back to 1.0, that would rank the broken entry highest.
                    if(!preg_match('/^(?:0(?:\.\d{1,3})?|1(?:\.0{1,3})?)$/', $match[1])) {
                        $quality = 0.0;
                        break;
                    }

                    $quality = (float) $match[1];
                    break;
                }

                // q=0 explicitly means that the locale is not acceptable.
                if($quality <= 0.0) continue;

                // Canonical form, so that "en-us" and "EN-US" collapse into one entry.
                $subtags = explode("-", strtolower($locale));

                foreach($subtags as $index => $subtag) {
                    if(0 === $index || !ctype_alpha($subtag)) continue;

                    if(2 === strlen($subtag)) $subtags[$index] = strtoupper($subtag);   // region
                    elseif(4 === strlen($subtag)) $subtags[$index] = ucfirst($subtag);  // script
                }

                $locale = implode("-", $subtags);

                // Keep the first occurrence, but let a later duplicate raise its quality.
                if(isset($accepted[$locale]) && $accepted[$locale]["quality"] >= $quality) continue;

                $accepted[$locale] = [
                    "locale"  => $locale,
                    "quality" => $quality,
                ];
            }

            $accepted = array_values($accepted);

            // Sort by quality
            usort($accepted, fn($a, $b) => $b["quality"] <=> $a["quality"]);

            return array_column($accepted, "locale");
        }

        public function getExecutionTime(): ?float {
            if(!isset($this->input->SERVER["REQUEST_TIME_FLOAT"])) return null;
            return microtime(true) - $this->input->SERVER["REQUEST_TIME_FLOAT"];
        }

        /**
         * Returns the current URL
         * @return string Returns the current URL, consisting of the configured host followed by the raw REQUEST_URI (root directory, path and query string)
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
         * Returns the app domain as specified in the configuration (`host=`), with scheme, port and path stripped (`https://example.com:8080/app` becomes `example.com`). An IPv6 literal keeps its brackets (`[::1]`).
         * @return string the domain
         */
        public function getDomain(): string {
            // Without the scheme and everything from the first slash on
            $host = preg_replace('#^https?://#i', "", (string) config("host"));
            $host = explode("/", $host)[0];

            // The colons inside the brackets of an IPv6 literal are no port separator
            if(preg_match('#^\[[^\]]*\]#', $host, $literal)) return $literal[0];

            return explode(":", $host)[0];
        }

        /**
         * Gets the URL parameters (excluding the leading controller and action) specified by the path.
         * @param int $offset The offset from which to start. Can be -1 if action_fallback is used
         * @param int $length The amount of array elements that will be returned at the set offset. If null, every element will be returned
         * @param string $val If the length is 1 and $val is set, a Boolean will be returned. $val will be compared to the parameter
         * @return mixed[]|string|bool Array of parameters, the single parameter (or a Boolean comparison with $val) if length is 1, false if length is 1 and the parameter is missing
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
         * @return array{id: string, text: string} Associative array; both are empty strings when the URL segment is missing
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
         * Gets the user who made the request, also for guests (check isLoggedIn).
         * @return \ZubZet\Framework\Authentication\User Object of the requesting user
         */
        public function getRequestingUser(): User {
            return user();
        }

        /**
         * Gets the URL base path of the app ("/" plus rootDirectory, with trailing slash).
         * @return string URL base path used to prefix links and cookie paths
         */
        public function getRootFolder(): string {
            return config("rootFolder");
        }

        public function checkSuperPermission(string $permission, bool $boolResult = false): bool {
            return $this->checkPermission($permission, $boolResult, true);
        }

        /**
         * Checks if the current user has a permission. If the user is not logged in, the login page is rendered and the script exits.
         * If the user is logged in but does not have the permission, the 403 page is rendered and the script exits. The permission "console" passes only on CLI.
         * @param string $permission Permission to check for
         * @param bool $boolResult If true, the function will return false instead of rendering the login/403 page and exiting
         * @param bool $includeSuperUser If true, the permission may also be held by the executing user (the user acting as another one)
         * @return bool True if the permission is granted (otherwise false with $boolResult, or the script exits)
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
         * Checks if the current user belongs to an organization. If the user is not logged in, they will be redirected to the login page.
         * If the user is logged in but does not belong to an organization, they will be redirected to 403.
         * @param bool $boolResult If true, the function will return a boolean result instead of redirecting
         */
        public function requireOrganization(bool $boolResult = false): bool {
            if(!user()->isLoggedIn) {
                if($boolResult) return false;
                zubzet()->executePath(["login", "index"]);
                exit;
            }

            // byId() skips removed organizations, so their former members are denied
            $hasOrganization = !is_null(user()->orgId) && !is_null(Organization::byId(user()->orgId));

            if(!$hasOrganization) {
                if($boolResult) return false;
                zubzet()->executePath(["error", "403"]);
                exit;
            }

            return true;
        }

        /**
         * Guards an action behind a recent two factor check. Answers true when the
         * session passed one inside `two_factor_freshness_seconds`, and for every
         * account that carries no two factor at all.
         *
         * Without $boolResult a stale session is answered with a json error carrying
         * `twoFactorRenew`, which Z.js turns into the two factor modal. Pass true to
         * answer the client yourself instead:
         *
         *     if(!$req->requireFreshTwoFactor(boolResult: true)) {
         *         return $res->error("Two factor required", ["twoFactorRenew" => true]);
         *     }
         *
         * @param ?int $freshnessSeconds How recent the check has to be, or null for the configured window
         * @param bool $boolResult Return the answer rather than ending the request
         * @return bool Whether the session is fresh enough
         */
        public function requireFreshTwoFactor(?int $freshnessSeconds = null, bool $boolResult = false): bool {
            if(!user()->isLoggedIn) {
                if($boolResult) return false;
                zubzet()->executePath(["login", "index"]);
                exit;
            }

            $session = Session::byToken(user()->getSessionToken());

            if(is_null($session) || $session->requireRenew($freshnessSeconds)) {
                if($boolResult) return false;
                response()->error("Two factor required", ["twoFactorRenew" => true]);
                exit;
            }

            return true;
        }

        /**
         * Checks if the request contains form data, i.e. the POST flag isFormData that Z.js ZForm sends (plain HTML form posts return false). When a form action is provided, it must match the submitted action.
         * When it contains form data, methods like validateForm() can be used.
         * @param string|null $formAction Optional action identifying one form among multiple forms
         * @return bool
         */
        public function hasFormData(?string $formAction = null): bool {
            if(!isset($this->input->POST["isFormData"])) return false;
            if($formAction === null) return true;
            return ($this->input->POST["formAction"] ?? null) === $formAction;
        }

        /**
         * Checks if the POST field action loosely equals $type; it does not verify that the request came via AJAX
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
         * @return ?string The raw body content of the request (Returns null if empty or unavailable, so a body of "0" is null too).
         */
        public function getBody(): ?string {
            return $this->input->body;
        }

        /**
         * Decode the request body as JSON.
         *
         * Every well formed JSON value is accepted, whatever the method and the
         * Content-Type of the request are: an object or a list comes back as an
         * array, and a number, a string, a boolean or `null` as that value. A
         * request without a body gives `null`, so an optional body reads as
         * `$req->getJson() ?? []`. Large integers come back as strings instead
         * of lossy floats.
         *
         * @param ?int $maxBytes Longest accepted body in bytes, no limit when null
         * @param int $maxDepth Deepest accepted nesting, 512 is the limit PHP itself applies
         * @return mixed The decoded value with objects as associative arrays, null without a body
         * @throws \UnexpectedValueException With the code 413 for a body longer than $maxBytes
         * @throws \JsonException If the body is not well formed JSON
         */
        public function getJson(?int $maxBytes = null, int $maxDepth = 512): mixed {
            // No body is no JSON, which is something else than malformed JSON
            $body = $this->getBody();
            if(is_null($body)) return null;

            if(!is_null($maxBytes) && strlen($body) > $maxBytes) {
                throw new \UnexpectedValueException("Body too large", 413);
            }

            // Syntax errors, a BOM, invalid UTF-8, control characters and too deep nesting throw a JsonException
            return json_decode($body, true, $maxDepth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        }

        /**
         * Runs the CSRF check, additionally covering methods normally exempt as safe
         * (e.g. "GET"), so this request also gets CSRF-checked for them.
         * A rejected request is redirected to 403 and the script ends.
         *
         * @param string ...$additionalMethods Methods to remove from the safe-method
         *        exemption for this call, "GET" when none is given
         */
        public function csrfBlockAdditionalMethod(string ...$additionalMethods): void {
            // Protect GET by default, when called without arguments.
            if(empty($additionalMethods)) $additionalMethods = ["GET"];

            if(CSRF::verify(...$additionalMethods)) return;

            zubzet()->executePath(["error", "403"]);
            exit;
        }
    }
?>
