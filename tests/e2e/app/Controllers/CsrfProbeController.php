<?php

    use ZubZet\Framework\Security\CSRF;

    // Probe for tests/cypress/e2e/core/csrf.cy.js. No DB access - no seed.
    class CsrfProbeController extends z_controller {

        // An action on the plain POST fields, without any Z.js marker
        public function action_plain(Request $req, Response $res) {
            return $res->success();
        }

        // Reached by convention under a group that opted out in CsrfRoutes.php
        public function action_exempt(Request $req, Response $res) {
            return $res->success();
        }

        // Opted out in CsrfRoutes.php, sends a guest on to the login through checkPermission()
        public function action_guarded(Request $req, Response $res) {
            $req->checkPermission("admin.panel");

            return $res->success();
        }

        // What a raw HTML form embeds to carry the token
        public function action_field(Request $req, Response $res) {
            echo CSRF::field();
        }
    }

?>
