<?php

    class ExceptionController extends z_controller {

        public function action_whoops(Request $req, Response $res) {
            throw new \Exception("This is a test exception to check if Whoops is disabled in production mode.");
        }

    }

?>
