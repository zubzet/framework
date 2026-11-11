<?php

    use ZubZet\Framework\Message\Response;

    /**
     * Exercises the layout resolution chain from Resolver\DefaultLayout:
     *   explicit arg > instance default > global default > framework default
     *
     * The branches below the explicit argument are unit tested; this
     * controller keeps the one that needs a rendered page: the explicit
     * render() argument beats both defaults. Cypress inspects the response
     * body for the matching layout marker.
     */
    class LayoutResolutionController extends z_controller {

        public function action_explicitwins(Request $req, Response $res) {
            Response::setGlobalDefaultLayout("layout/alt_layout");
            $res->setDefaultLayout("layout/new_layout");
            $res->render("core/render", ["data" => "Data"], "layout/default_layout.php");
        }

    }
