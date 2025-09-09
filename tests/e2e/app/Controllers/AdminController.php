<?php

    class AdminController extends z_controller {

        public function action_loginas(Request $req, Response $res) {
            $userId = $req->getRequestingUser()->userId ?? 0;
            $res->loginAs(1);

            echo($userId);
        }

    }

?>