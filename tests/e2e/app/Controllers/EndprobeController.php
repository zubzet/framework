<?php

    /**
     * Test fixtures for tests/cypress/e2e/core/end-of-request.cy.js.
     *
     * Every action ends the request in a different way and prints a marker
     * where the code must not continue.
     */
    class EndprobeController extends z_controller {

        // Nothing of the action runs after a view
        public function action_render(Request $req, Response $res) {
            $res->render("core/empty");
            echo "after-render";
        }

        // Run from the console by the spec
        public function action_rest(Request $req, Response $res) {
            $res->success();
            echo "after-success";
        }

        // Status, headers and output of a stopped request reach the client
        public function action_stop(Request $req, Response $res) {
            http_response_code(202);
            header("X-Endprobe: stopped");
            echo "before-stop";

            $res->stop();
            echo "after-stop";
        }

        public function afterware(Request $req, Response $res) {
            echo "afterware-ran";
        }

    }

?>
