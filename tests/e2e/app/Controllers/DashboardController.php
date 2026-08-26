<?php

    class DashboardController extends z_controller {

        public function action_index(\Request $req, \Response $res) {
            echo '<span data-test="dashboard-controller">Dashboard Controller</span>';

            echo("<br><br><br>");
            echo __("dashboard.welcome", domain: "admin");
            echo("<br><br><br>");
            echo __("welcome", domain: "admin");

            // Placeholders are spelled the way the catalogue spells them.
            echo("<br><br><br>");
            echo __("dashboard.greeting", [
                "%name%" => "Ada",
                "%date%" => date("d.m.Y"),
            ], domain: "admin");

            // %count% picks the interval and is substituted like any other placeholder.
            echo("<br><br><br>");
            foreach([0, 1, 7] as $count) {
                echo __("dashboard.messages", ["%count%" => $count], domain: "admin");
                echo("<br>");
            }
        }

    }

?>