<?php 
    class IndexController extends z_controller {

        public function action_index($req, $res) {
            $res->render("index.php", [], "layout/default_layout.php");
        }
    }
?>