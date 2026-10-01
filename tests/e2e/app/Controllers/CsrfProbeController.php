<?php

    use ZubZet\Framework\Security\CSRF;

    // Probe for tests/cypress/e2e/core/csrf.cy.js. No DB access - no seed.
    class CsrfProbeController extends z_controller {

        // An action on the plain POST fields, which demands the token itself
        public function action_enforced(Request $req, Response $res) {
            CSRF::enforce();

            return $res->success();
        }

        // What a raw HTML form embeds to carry the token
        public function action_field(Request $req, Response $res) {
            echo CSRF::field();
        }
    }

?>
