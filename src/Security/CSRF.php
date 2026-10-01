<?php

    namespace ZubZet\Framework\Security;

    /**
     * Stateless double-submit-cookie CSRF defense: the server issues a
     * `z_csrf` cookie, Z.js echoes it back as an `X-CSRF-Token` header, and
     * non-GET requests must present both matching. A cross-origin attacker
     * gets the cookie sent for them but cannot read it, so they cannot fill
     * in the header.
     *
     * Constructing the object is the whole defense: it issues the token and
     * runs the check. The Router does that once per request; an action that
     * cannot rely on the marker calls `CSRF::enforce()`.
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

        /** The token of this request, issued or reused by ensureToken(). */
        private static string $token = '';

        /**
         * @param bool $enforce Verify regardless of the marker, which lives in
         * the request body and is therefore attacker-controlled. Needed
         * wherever an action runs on the plain POST fields and never requires
         * the marker itself.
         */
        public function __construct(bool $enforce = false) {
            $this->ensureToken();

            // Only verify if the request is a Z.js request or the caller explicitly asks for it.
            $shouldVerify = $enforce
                || isset(request()->input->POST['isFormData'])
                || isset(request()->input->POST['action']);

            if(!$shouldVerify) return;

            // Safe methods do not require a CSRF token
            $method = strtoupper(request()->input->SERVER['REQUEST_METHOD'] ?? 'GET');
            if(in_array($method, self::SAFE, true)) return;

            $cookie = request()->getCookie(self::COOKIE) ?? null;
            // A raw HTML form cannot set the header and sends the field instead
            $header = request()->input->SERVER['HTTP_X_CSRF_TOKEN'] ?? request()->input->POST[self::FIELD] ?? null;

            if(empty($cookie) || empty($header) || !hash_equals($cookie, $header)) {
                http_response_code(403);
                header('Content-Type: application/json');
                response()->generateRestError(403, 'csrf token mismatch');
                return;
            }
        }

        /**
         * Verify regardless of the marker. For an action that runs on the
         * plain POST fields and never requires the marker itself.
         */
        public static function enforce(): void {
            new self(enforce: true);
        }

        /**
         * Hidden input for a raw HTML form whose action verifies with
         * `CSRF::enforce()`. The Router has already issued the token for
         * this request.
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
