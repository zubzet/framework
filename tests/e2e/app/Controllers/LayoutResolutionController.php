<?php

    use ZubZet\Framework\Message\Response;

    /**
     * Exercises the layout resolution chain from HandlesDefaultLayout:
     *   explicit arg > instance default > global default > framework default
     *
     * Each action drives one branch of the resolution and renders directly;
     * cypress inspects the response body for the matching layout marker.
     */
    class LayoutResolutionController extends z_controller {

        public function action_neither(Request $req, Response $res) {
            $res->render("core/render", ["data" => "Data"]);
        }

        public function action_globalonly(Request $req, Response $res) {
            Response::setGlobalDefaultLayout("layout/alt_layout");
            $res->render("core/render", ["data" => "Data"]);
        }

        public function action_instanceonly(Request $req, Response $res) {
            $res->setDefaultLayout("layout/new_layout");
            $res->render("core/render", ["data" => "Data"]);
        }

        public function action_instanceoverglobal(Request $req, Response $res) {
            Response::setGlobalDefaultLayout("layout/alt_layout");
            $res->setDefaultLayout("layout/new_layout");
            $res->render("core/render", ["data" => "Data"]);
        }

        public function action_explicitwins(Request $req, Response $res) {
            Response::setGlobalDefaultLayout("layout/alt_layout");
            $res->setDefaultLayout("layout/new_layout");
            $res->render("core/render", ["data" => "Data"], "layout/default_layout.php");
        }

        public function action_pushpopinstance(Request $req, Response $res) {
            $res->pushDefaultLayout("layout/new_layout");
            $res->render("core/render", ["data" => "Pushed"]);
            $res->popDefaultLayout();
            $res->render("core/render", ["data" => "Popped"]);
        }

        public function action_pushpopglobal(Request $req, Response $res) {
            Response::pushGlobalDefaultLayout("layout/alt_layout");
            $res->render("core/render", ["data" => "Pushed"]);
            Response::popGlobalDefaultLayout();
            $res->render("core/render", ["data" => "Popped"]);
        }

        public function action_underflowinstance(Request $req, Response $res) {
            try {
                $res->popDefaultLayout();
            } catch (\UnderflowException $e) {
                echo "threw:" . $e->getMessage();
            }
        }

        public function action_underflowglobal(Request $req, Response $res) {
            try {
                Response::popGlobalDefaultLayout();
            } catch (\UnderflowException $e) {
                echo "threw:" . $e->getMessage();
            }
        }

        public function action_pushpopnested(Request $req, Response $res) {
            $res->pushDefaultLayout("layout/alt_layout");
            $res->render("core/render", ["data" => "Outer"]);
            $res->pushDefaultLayout("layout/new_layout");
            $res->render("core/render", ["data" => "Inner"]);
            $res->popDefaultLayout();
            $res->render("core/render", ["data" => "AfterPop"]);
            $res->popDefaultLayout();
            $res->render("core/render", ["data" => "AfterAllPops"]);
        }

    }
