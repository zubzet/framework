<?php

    class RouteDenyController extends z_controller  {

        public function action_check(Request $req, Response $res) {
            print_r("Middleware Deny Executed\n");
        }

    }

?>