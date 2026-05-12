<?php

    // Controller intentionally guarded by Request::checkPermission("console")
    // from its __construct. This exercises the controller-level CLI gate
    // pattern: CLI invocations of any action pass; HTTP requests get 403
    // before the action runs. Covered by tests/cypress/e2e/advanced/command.cy.js.
    class ConsoleController extends z_controller {

        public function __construct(Request $req, Response $res) {
            $req->checkPermission("console");
        }

        public function action_run(Request $req, Response $res): void {
            echo "console-only action ran";
        }
    }

?>
