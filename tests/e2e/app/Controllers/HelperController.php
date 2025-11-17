<?php

    class HelperController extends z_controller {

        public function action_zubzet(Request $req, Response $res) {
            print_r(zubzet()->config["custom_value"]);
        }

        public function action_model(Request $req, Response $res) {
            echo model("Helper")->testCall();
        }

        public function action_request(Request $req, Response $res) {
            print_r(request()->getParameters(0, 1));
        }

        public function action_response(Request $req, Response $res) {
            response()->generateRest([
                "response" => "success"
            ]);
        }

        public function action_config(Request $req, Response $res) {
            print_r(config("custom_value"));
        }

        public function action_user(Request $req, Response $res) {
            print_r(user()->isLoggedIn ? "logged in" : "not logged in");
        }

        public function action_db(Request $req, Response $res) {
            is_null(db()->cakePHPDatabase) ? print_r("no database") : print_r("database connected");
        }

        public function action_view(Request $req, Response $res) {
            view("core/render", [
                "data" => "HelperFunction"
            ]);
        }

    }

?>