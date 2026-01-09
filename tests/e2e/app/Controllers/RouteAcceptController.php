<?php

    class RouteAcceptController extends z_controller  {

        public function action_check(Request $req, Response $res) {
            print_r("Middleware Accept Executed\n");
        }

    }
?>