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

    }

?>
