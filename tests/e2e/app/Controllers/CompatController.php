<?php

    /**
     * Exercises Blade/Katana compatibility guarantees through the real render path:
     *  - action_probe: a migrated legacy view whose literal {{ }}, {!! !!} and
     *    {{-- --}} markers must survive verbatim (see Views/compat/probe).
     *  - action_component: an authored .blade.php using an anonymous component.
     * Covered by tests/cypress/e2e/core/blade-compat.cy.js.
     */
    class CompatController extends z_controller {

        public function action_probe(Request $req, Response $res) {
            return $res->render("compat/probe", ["compatData" => "OPT_DATA_OK"]);
        }

        public function action_component(Request $req, Response $res) {
            return $res->render("compat/component");
        }
    }
