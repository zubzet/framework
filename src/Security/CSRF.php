<?php

    namespace ZubZet\Framework\Security;

    /**
     * Stateless double-submit-cookie CSRF defense: the server issues a
     * `z_csrf` cookie, Z.js echoes it back as an `X-CSRF-Token` header, and
     * non-GET requests must present both matching. A cross-origin attacker
     * gets the cookie sent for them but cannot read it, so they cannot fill
     * in the header.
     *
     * The Router constructs the object to issue the token. Dispatch calls
     * `CSRF::verify()` before the middlewares and the action of a route run,
     * unless the route or one of its groups opted out with `withoutCsrf()`.
     *
     * The cookie is host-only on purpose, even under
     * `login_scope_allow_subdomains` - see docs/core-features/csrf-protection.
     */
    final class CSRF {

        public const COOKIE = "z_csrf";
        public const HEADER = "X-CSRF-Token";
        public const FIELD = "_csrf";
        private const SAFE = ["GET", "HEAD", "OPTIONS"];
        private const LIFETIME = 60 * 60 * 24 * 30; // 30 days

        // What a cross-site form or no-cors fetch can send without a CORS preflight; "" is a request without a body
        private const SIMPLE_CONTENT_TYPES = ["", "application/x-www-form-urlencoded", "multipart/form-data", "text/plain"];

        // Authorization schemes the browser attaches by itself, to cross-site requests too
        private const BROWSER_AUTH_SCHEMES = ["basic", "digest", "negotiate", "ntlm"];

        /** The token of this request, issued or reused by ensureToken(). */
        private static string $token = '';

        public function __construct() {
            $this->ensureToken();
        }

        public static function verify(): void {
            // Safe methods do not require a CSRF token
            $method = strtoupper(request()->input->SERVER['REQUEST_METHOD'] ?? 'GET');
            if(in_array($method, self::SAFE, true)) return;

            // Any other content type needs a CORS preflight to be sent cross-site, so it cannot be forged
            $contentType = explode(";", request()->input->SERVER['CONTENT_TYPE'] ?? "")[0];
            $contentType = strtolower(trim($contentType));
            if(!in_array($contentType, self::SIMPLE_CONTENT_TYPES, true)) {
                return;
            }

            // Same for an Authorization header set by script; mod_php keeps it out of $_SERVER, not out of getallheaders()
            $headers = [];
            if(function_exists("getallheaders")) {
                $headers = array_change_key_case(getallheaders(), CASE_LOWER);
            }

            $authorization = trim($headers["authorization"] ?? "");
            $authScheme = strtolower(explode(" ", $authorization)[0]);
            if(!empty($authScheme) && !in_array($authScheme, self::BROWSER_AUTH_SCHEMES, true)) {
                return;
            }

            $cookie = request()->getCookie(self::COOKIE) ?? null;
            // A raw HTML form cannot set the header and sends the field instead
            $header = request()->input->SERVER['HTTP_X_CSRF_TOKEN'] ?? request()->input->POST[self::FIELD] ?? null;

            if(empty($cookie) || empty($header) || !hash_equals($cookie, $header)) {
                http_response_code(403);
                header('Content-Type: application/json');
                response()->generateRestError(403, 'csrf token mismatch');
            }
        }

        /**
         * Hidden input for a raw HTML form, which cannot set the header. The
         * Router has already issued the token for this request.
         */
        public static function field(): string {
            return '<input type="hidden" name="' . self::FIELD . '" value="' . e(self::$token) . '">';
        }

        private function ensureToken(): void {
            $existing = request()->getCookie(self::COOKIE);

            // Reuse the token the browser already holds.
            if($existing) {
                self::$token = $existing;
                return;
            }
            $token = bin2hex(random_bytes(20));

            // No domain attribute: sibling subdomains must not read this.
            response()->setFrameworkCookie(self::COOKIE, $token, self::LIFETIME, hostOnly: true);

            // The cookie only reaches the browser with the response, so make
            // the token visible within this request too.
            request()->input->COOKIE[self::COOKIE] = $token;
            self::$token = $token;
        }
    }
