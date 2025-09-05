<?php

    class FallbackController extends z_controller {

        public function action_fallback(Request $req, Response $res) {
            echo ":)";
        }

    }

?>