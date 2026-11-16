<?php

    /**
     * Probes for global references used by
     * tests/cypress/e2e/support/global-references.cy.js.
     */
    class HelperController extends z_controller {

        public function action_model(Request $req, Response $res) {
            echo model("Helper")->testCall();
        }

        public function action_response(Request $req, Response $res) {
            response()->generateRest([
                "response" => "success",
            ]);
        }

        public function action_view(Request $req, Response $res) {
            view("core/render", [
                "data" => "HelperFunction",
            ]);
        }

        public function action_view_dotted(Request $req, Response $res) {
            // Same view as action_view, addressed with Laravel-style dot notation
            // ("core.render") instead of the slash path ("core/render").
            view("core.render", [
                "data" => "HelperFunction",
            ]);
        }

    }

?>
