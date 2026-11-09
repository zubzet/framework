<?php

    namespace ZubZet\Framework\Security;

    final class CSRF {

        // The Sec-Fetch-Site values each csrf_allow lets through
        private const ALLOWED_SITES = [
            // Allow only requests from the same origin or without a browser (curl, Postman, etc.)
            "same-origin" => [
                "same-origin",
                "none"
            ],
            // Allow requests from the same origin, subdomains and without a browser
            "same-site" => [
                "same-origin",
                "same-site",
                "none"
            ],
            // Allow every site, only csrf_blocked_origins still rejects
            "any" => [
                "same-origin",
                "same-site",
                "cross-site",
                "none"
            ],
        ];

        private const SAFE_METHODS = [
            "GET",
            "HEAD",
            "OPTIONS"
        ];

        /**
         * Checks the current request against the csrf_* settings.
         *
         * @param string ...$additionalMethods Methods to remove from the safe-method
         *        exemption for this call, e.g. "GET", so they get CSRF-checked too
         * @return bool Whether the request is verified
         */
        public static function verify(string ...$additionalMethods): bool {
            // Method names are case insensitive, like the request method below
            $safeMethods = array_diff(self::SAFE_METHODS, array_map("strtoupper", $additionalMethods));

            // Safe methods never change state, skip them entirely
            $method = strtoupper(request()->input->SERVER["REQUEST_METHOD"] ?? "GET");
            if(in_array($method, $safeMethods, true)) return true;

            $fetchSite = request()->input->SERVER["HTTP_SEC_FETCH_SITE"] ?? null;
            $source = request()->input->SERVER["HTTP_ORIGIN"] ?? null;

            // Skip CSRF check for requests without a browser (curl)
            if(is_null($fetchSite) && is_null($source)) return true;

            // A missing header is an empty string from here on
            $fetchSite = strtolower(trim($fetchSite ?? ""));
            $source = strtolower(trim($source ?? ""));

            // Validated before any decision, so a misconfiguration surfaces on every browser request
            $allow = strtolower(config("csrf_allow", default: "same-origin"));
            if(!array_key_exists($allow, self::ALLOWED_SITES)) {
                throw new \RuntimeException("Invalid csrf_allow setting: $allow");
            }

            // Origin: null hides the source (no-referrer, a sandboxed iframe), same as a missing
            // Origin. Neither can ever match csrf_trusted_origins or csrf_blocked_origins, so an
            // unknown source is only accepted from the same origin or without a page at all.
            // Under csrf_allow = any every site may, so there is nothing to tell apart
            if("any" !== $allow && ("" === $source || "null" === $source)) {
                return in_array($fetchSite, self::ALLOWED_SITES["same-origin"], true);
            }

            $trusted = self::parseConfigList("csrf_trusted_origins");
            $blocked = self::parseConfigList("csrf_blocked_origins");

            // When SEC-FETCH-SITE is set, only allow requests from the allowed sites or trusted origins
            if("" !== $fetchSite && !in_array($fetchSite, self::ALLOWED_SITES[$allow], true)) {
                return in_array($source, $trusted, true);
            }

            return !in_array($source, $blocked, true);
        }

        /** @return string[] The lowercase, trimmed and non-empty entries of a comma separated setting */
        private static function parseConfigList(string $key): array {
            $entries = explode(",", strtolower(config($key, default: "")));
            return array_filter(array_map("trim", $entries));
        }
    }

?>
