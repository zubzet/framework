<?php

    /**
     * Renders renderprobe/namespace, which places the framework's namespaced
     * <x-zubzet::head/> next to a plain app <x-head/> so the test can prove the
     * two never shadow each other (katanaphp/blade#66).
     * Covered by core/component-namespace.cy.js.
     */
    class ComponentNamespaceController extends z_controller {

        public function action_isolation(Request $req, Response $res) {
            return $res->render("renderprobe/namespace", [], "layout/empty");
        }

        // Components that read the framework's standard $opt without being handed it.
        // Covered by rendering/component-opt.cy.js.
        public function action_standardOpt(Request $req, Response $res) {
            return $res->render("renderprobe/standard-opt", ["callerValue" => "CALLER_VALUE"], "layout/empty");
        }

        // Hands the whole $opt to an app component, which the framework refuses.
        // Covered by rendering/component-opt.cy.js.
        public function action_reservedOpt(Request $req, Response $res) {
            return $res->render("renderprobe/reserved-opt", [], "layout/empty");
        }

    }

?>
