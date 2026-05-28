<?php

    use ZubZet\Framework\Message\Response;

    class LayoutConstructorController extends z_controller {

        public function __construct(Request $req, Response $res) {
            // Instance-scope default set in the constructor: verifies the
            // Response handed to the action is the same instance that was
            // configured here.
            $res->setDefaultLayout("layout/new_layout");
        }

        public function action_render(Request $req, Response $res) {
            $res->render("core/render", ["data" => "Data"]);
        }

    }
