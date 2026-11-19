<?php

    /**
     * Test fixtures for tests/cypress/e2e/core/end-of-request.cy.js.
     *
     * Every action ends the request in a different way and prints a marker
     * where the code must or must not continue.
     */
    class EndprobeController extends z_controller {

        // Nothing of the action runs after a view
        public function action_render(Request $req, Response $res) {
            $res->render("core/empty");
            echo "after-render";
        }

        public function action_rest(Request $req, Response $res) {
            $res->success();
            echo "after-success";
        }

        // A guard that is not returned still protects what follows
        public function action_guard(Request $req, Response $res) {
            $req->checkPermission("endprobe.missing");
            echo "behind-guard";
        }

        // The end of a request is no \Exception
        public function action_catch(Request $req, Response $res) {
            try {
                $res->success();
            } catch(\Exception $e) {
                echo "caught";
            }
            echo "after-catch";
        }

        public function action_finally(Request $req, Response $res) {
            try {
                $res->success();
            } finally {
                echo "finally-ran";
            }
        }

        public function afterware(Request $req, Response $res) {
            echo "afterware-ran";
        }

        public function blocking(Request $req, Response $res) {
            echo "blocked";
        }

    }

?>
