<?php

    class AdminController extends z_controller {

        public function action_loginas(Request $req, Response $res) {
            echo($req->getRequestingUser()->userId ?? 0);

            $res->loginAs(1);
        }

    }

?>