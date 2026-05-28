<?php

    class DashboardController extends z_controller {

        public function action_index(\Request $req, \Response $res) {
            echo '<span data-test="dashboard-controller">Dashboard Controller</span>';
        }

    }

?>