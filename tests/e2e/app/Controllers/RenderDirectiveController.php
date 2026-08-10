<?php

    /**
     * Renders renderprobe/directives, which exercises the @auth / @guest Blade
     * directives bound by Rendering\Katana\Hooks. The view is layout-free
     * (layout/empty) so the test reads only the directive branches.
     * Covered by core/render-directives.cy.js.
     */
    class RenderDirectiveController extends z_controller {

        public function action_directives(Request $req, Response $res) {
            return $res->render("renderprobe/directives", [], "layout/empty");
        }

    }

?>
