<?php

    class DashboardController extends z_controller {

        public function action_index(Request $req, Response $res) {
            logger("zubzet")->info("DashboardController index action called");
            die;
            echo "Dashboard Controller";
        }

    }

?>