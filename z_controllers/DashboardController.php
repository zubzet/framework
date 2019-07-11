<?php 

    class DashboardController {
        
        public function action_index($req, $res) {
            $res->render("dashboard.php", [
                "date" => date('l \t\h\e jS \of F H:i')
            ], "layout/default.php");
        }

	}

?>