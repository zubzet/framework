<?php

    use ZubZet\Framework\Security\Csrf;

    // Probe for tests/cypress/e2e/core/csrf.cy.js. No DB access - no seed.
    class CsrfProbeController extends z_controller {

        // An action on the plain POST fields, which demands the header itself
        public function action_enforced(Request $req, Response $res) {
            new Csrf(enforce: true);

            return $res->success();
        }
    }

?>
