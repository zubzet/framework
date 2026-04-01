<?php

    class AdminController extends z_controller {

        public function action_loginas(Request $req, Response $res) {
            $userId = $req->getRequestingUser()->userId ?? 0;
            $res->loginAs(1);

            echo($userId);
        }

        public function action_e2e_loginas_exec(Request $req, Response $res) {
            $userId = $req->getRequestingUser()->userId ?? 0;
            $res->loginAs(3, $userId);
        }

    }

?>