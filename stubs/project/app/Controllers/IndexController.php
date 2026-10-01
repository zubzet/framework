<?php

    class IndexController extends z_controller {

        public function action_index(Request $req, Response $res) {
            return $res->render("index", [
                "examples" => $req->getModel("Example")->getAll(),
            ]);
        }

    }
?>
