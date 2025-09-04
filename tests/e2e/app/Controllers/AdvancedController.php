<?php

    class AdvancedController extends z_controller {

        public function action_aliases(Request $req, Response $res) {
            $res->reroute(["core", "action"], true);
        }

        public function action_command(Request $req, Response $res) { //TODO: ???

        }

    }

?>