<?php

    class BladeOneController extends z_controller  {

        public function action_auth(Request $req, Response $res) {
            $res->render("bladeone/auth.blade.php");
        }

    }

?>