<?php

    // Probe for tests/cypress/e2e/core/csrf.cy.js. No DB access - no seed.
    class CsrfProbeController extends z_controller {

        // Answers every method, so the real check can be tried with each of them
        public function action_plain(Request $req, Response $res) {
            return $res->success();
        }

        // Opts GET back into the check, the default without arguments
        public function action_optIn(Request $req, Response $res) {
            $req->csrfBlockAdditionalMethod();

            return $res->success();
        }

        // Opts GET and HEAD back in, written lowercase as the names are case insensitive
        public function action_optInHead(Request $req, Response $res) {
            $req->csrfBlockAdditionalMethod("get", "head");

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
    }

?>
