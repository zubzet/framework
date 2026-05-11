<?php

    class AdvancedController extends z_controller {

        public function action_aliases(Request $req, Response $res) {
            $res->reroute(["core", "action"], true);
        }

        public function action_command(Request $req, Response $res) {
            echo "Advanced Command Executed";
        }

        // Probe used by advanced/info-startup.cy.js to read the value that
        // `info:startup --pwd` writes through AutomatedSettings::set and that
        // Bootstrap\Configuration loads via parse_ini_string + AutomatedSettings::load.
        public function action_automatedHostWorkingDirectory(Request $req, Response $res) {
            echo config("automated_host_working_directory") ?? "";
        }

    }

?>