<?php

    use ZubZet\Framework\Security\CSRF;

    // Probe for tests/cypress/e2e/core/csrf.cy.js. No DB access - no seed.
    class CsrfProbeController extends z_controller {

        // Answers every method, so the real check can be tried with each of them
        public function action_plain(Request $req, Response $res) {
            return $res->success();
        }

        // Sends a guest on to the login through checkPermission()
        public function action_guarded(Request $req, Response $res) {
            $req->checkPermission("admin.panel");

            return $res->success();
        }

        public function action_reroute(Request $req, Response $res) {
            $res->reroute(["CsrfProbe", "plain"]);
        }

        // Runs the real check on the headers and settings of the JSON body:
        // {"headers": {"Sec-Fetch-Site": "...", "Origin": "..."}, "host": "...", "settings": {...}}
        // "host" is accepted for older callers and ignored. The probe request itself carries none of
        // these headers and passes the real check.
        public function action_decide(Request $req, Response $res) {
            $case = $req->getJson();
            $headers = array_change_key_case($case["headers"] ?? [], CASE_LOWER);
            $settings = $case["settings"] ?? [];

            // Fakes the request and the settings, then runs the real check
            foreach($headers as $name => $value) {
                $req->input->SERVER["HTTP_".strtoupper(str_replace("-", "_", $name))] = $value;
            }
            zubzet()->csrf_allow = (string) ($settings["csrf_allow"] ?? "same-origin");
            zubzet()->csrf_trusted_origins = (string) ($settings["csrf_trusted_origins"] ?? "");
            zubzet()->csrf_blocked_origins = (string) ($settings["csrf_blocked_origins"] ?? "");

            return $res->json(["verified" => CSRF::verify()]);
        }
    }

?>
