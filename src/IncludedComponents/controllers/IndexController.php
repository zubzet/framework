<?php 
    /**
     * This file holds the index controller
     */

    /**
     * The index controller handles by default all requests without a specified controller
     */
    class IndexController extends z_controller {

        /**
         * The index action
         * 
         * It will be called when no action is specified
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_index($req, $res) {
            $res->render("index.php", [], "layout/default_layout.php");
        }
    }
?>
