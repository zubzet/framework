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
     * cannot rely on the marker constructs its own with `enforce: true`.
     *
     * The cookie is host-only on purpose, even under
     * `login_scope_allow_subdomains` - see docs/core-features/csrf-protection.
     */
    final class Csrf {

        public const COOKIE = "z_csrf";
        public const HEADER = "X-CSRF-Token";
        private const SAFE = ["GET", "HEAD", "OPTIONS"];
        private const LIFETIME = 60 * 60 * 24 * 30; // 30 days

        /**
         * @param bool $enforce Verify regardless of the marker, which lives in
         * the request body and is therefore attacker-controlled. Needed
         * wherever an action runs on the plain POST fields and never requires
         * the marker itself.
         */
        public function __construct(bool $enforce = false) {
            $this->ensureToken();

            // Otherwise scoped to Z.Forms (`isFormData`) and Z.Request
            // (`_zReq`) so raw forms in application code keep working.
            $this->verify(
                $enforce
                || isset(request()->input->POST['isFormData'])
                || isset(request()->input->POST['_zReq'])
            );
        }

        private function ensureToken(): string {
            $existing = request()->getCookie(self::COOKIE);

            // Reuse the token only if it still has the shape we issue.
            if($existing) return $existing;
            $token = bin2hex(random_bytes(20));

            // On by default; only a non-HTTPS deployment turns this off.
            $secure = filter_var(config("csrf_secure", default: true), FILTER_VALIDATE_BOOLEAN);

            // No domain attribute: sibling subdomains must not read this.
            setcookie(self::COOKIE, $token, [
                'expires' => time() + self::LIFETIME,
                'path' => '/',
                'secure' => $secure,
                'httponly' => false, // JS must read it
                'samesite' => 'Lax',
            ]);

            // The cookie only reaches the browser with the response, so make
            // the token visible to verify() within this request too.
            request()->input->COOKIE[self::COOKIE] = $token;

            return $token;
        }

        private function verify(bool $required): void {
            $method = strtoupper(request()->input->SERVER['REQUEST_METHOD'] ?? 'GET');
            if(\in_array($method, self::SAFE, true)) return;

            if(!$required) return;

            $cookie = request()->getCookie(self::COOKIE) ?? '';
            $header = request()->input->SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

            if($cookie === '' || $header === '' || !hash_equals($cookie, $header)) {
                http_response_code(403);
                header('Content-Type: application/json');
                response()->generateRestError(403, 'csrf token mismatch');
                return;
            }
        }
    }
