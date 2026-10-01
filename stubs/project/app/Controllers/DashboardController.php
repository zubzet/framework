<?php

    class DashboardController extends z_controller {

        public function action_index(Request $req, Response $res) {
            $examples = $req->getModel("Example")->getExamples();

            return $res->render("example/dashboard", [
                "examples" => $examples,
            ]);
        }

    }

?>