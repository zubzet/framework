<?php 

	class ErrorController {

        public function action_403($req, $res) {
            $res->render("403.php", [], "layout/min_layout.php");
        }


        public function action_404($req, $res) {
            $res->render("404.php", [], "layout/min_layout.php");
        }

        public function action_500($req, $res) {
            $res->render("500.php", [], "layout/min_layout.php");
        }
        
	}

?>