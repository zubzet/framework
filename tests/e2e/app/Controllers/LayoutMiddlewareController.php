<?php

    use ZubZet\Framework\Message\Response;

    class LayoutMiddlewareController extends z_controller {

        public function Layout_Middleware_SetDefault(Request $req, Response $res) {
            // Middleware has the Response, so we set the per-instance default.
            $res->setDefaultLayout("layout/new_layout");
            return true;
        }

        public function action_render(Request $req, Response $res) {
            return $res->render("core/render", [
                "data" => "Data",
            ]);
        }

    }
