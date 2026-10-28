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

        /**
         * Checks the current request against the csrf_* settings.
         * 
         * @return bool Whether the request is verified
         */
        public static function verify(): bool {
            $fetchSite = request()->input->SERVER["HTTP_SEC_FETCH_SITE"] ?? null;
            $source = request()->input->SERVER["HTTP_ORIGIN"] ?? null;

            // Skip CSRF check for requests without a browser (curl)
            if(is_null($fetchSite) && is_null($source)) return true;

            // A missing header is an empty string from here on
            $fetchSite = strtolower(trim($fetchSite ?? ""));
            $source = strtolower(trim($source ?? ""));

            $allow = strtolower(config("csrf_allow", default: "same-origin"));
            if(!array_key_exists($allow, self::ALLOWED_SITES)) {
                throw new \RuntimeException("Invalid csrf_allow setting: $allow");
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
