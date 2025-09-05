<?php 
    /**
     * This file holds the error controller
     */

    /**
     * The error controller handles all bad requests. Other controllers can redirect to this one.
     */
	class ErrorController {

        /**
         * Action for a 403 Error
         * 
         * Access Denies
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_403($req, $res) {
            http_response_code(403);
            $res->render("403.php", [], "layout/min_layout.php");
        }

        /**
         * Action for a 404 Error
         * 
         * Not Found
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_404($req, $res) {
            http_response_code(404);
            $res->render("404.php", [], "layout/min_layout.php");
        }

        /**
         * Action for a 500 Error
         * 
         * Internal Server Error
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_500($req, $res) {
            http_response_code(500);
            $res->render("500.php", [], "layout/min_layout.php");
        }
        
	}

?>