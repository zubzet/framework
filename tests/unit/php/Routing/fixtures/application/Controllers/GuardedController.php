<?php

    // Its actions are found by their name and guarded by the groups of Routes/ProbeRoutes.php
    class GuardedController {

        public function action_returned(Request $req, Response $res) {
            echo "returned";
        }

        public function action_rest(Request $req, Response $res) {
            $res->success();
            echo "after-success";
        }

        public function action_blocked(Request $req, Response $res) {
            echo "behind-block";
        }

    }

?>
