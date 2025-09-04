<?php

    class CoreController extends z_controller {

        public function action_index(Request $req, Response $res) {
            echo "Controller Index";
        }

        public function action_action(Request $req, Response $res) {
            echo "Controller Action";
        }

        public function action_fallback(Request $req, Response $res) {
            echo "Controller Fallback";
        }

        public function action_parameters(Request $req, Response $res) {
            print_r($req->getParameters());
        }

        public function action_parameters1(Request $req, Response $res) {
            print_r($req->getParameters(0, 1, "TestParameter") ? "Ja" : "Nein");
        }

        public function action_parameters2(Request $req, Response $res) {
            print_r($req->getParameters(0, 2));
        }

        public function action_model(Request $req, Response $res) {
            echo($req->getModel("Core")->getData());
        }

        public function action_modelinsert(Request $req, Response $res) {
            $req->getModel("Core")->insertData("TestData");
            return $res->render("core/model", [
                "data" => $req->getModel("Core")->getModelTestsInsert()
            ]);
        }

        public function action_modelselectline(Request $req, Response $res) {
            return $res->render("core/model", [
                "data" => $req->getModel("Core")->getModelTestsLine()
            ]);
        }

        public function action_modelcount(Request $req, Response $res) {
            return $res->render("core/model", [
                "data" => $req->getModel("Core")->getModelCount()
            ]);
        }

        public function action_modellastid(Request $req, Response $res) {
            return $res->render("core/model", [
                "data" => $req->getModel("Core")->getModelLastId()
            ]);
        }

        public function action_modelselectarray(Request $req, Response $res) {
            return $res->render("core/model", [
                "data" => $req->getModel("Core")->getModelTestsArray()
            ]);
        }

        public function action_render(Request $req, Response $res) {
            return $res->render("core/render", [
                "data" => "Data",
            ]);
        }

        public function action_renderlayout(Request $req, Response $res) {
            return $res->render("core/render", [
                "data" => "Data",
            ], "core/layout");
        }

        public function action_permission(Request $req, Response $res) {
            $req->checkPermission("core.permissions");
            echo("Permissions");
        }

        public function action_permission1(Request $req, Response $res) {
            return $res->render("core/permission");
        }

        public function action_localization(Request $req, Response $res) {
            return $res->render("core/localization.php");
        }

        public function action_configuration(Request $req, Response $res) {
            echo ($res->getBooterSettings("custom_value"));
        }

        public function action_rest(Request $req, Response $res) {
            $res->generateRest([
                "Response" => "Test"
            ]);
        }

        public function action_resterr(Request $req, Response $res) {
            $res->generateRestError(400,"TestErr");
        }

        public function action_get(Request $req, Response $res) {
            print_r($req->getGet("TestGet"));
        }

        public function action_post(Request $req, Response $res) {
            echo($req->getPost("TestPost"));
        }

        public function action_file(Request $req, Response $res) {
            $File = $req->getFile("file");
            echo($File["name"]);
        }

        public function action_cookiesset(Request $req, Response $res) {
            $res->setCookie('testCookie', 'cookieValue', time() + 3600, '/', '', false, true);
        }

        public function action_cookieget(Request $req, Response $res) {
            print_r($req->getCookie("testCookie"));
        }

        public function action_cookieunset(Request $req, Response $res) {
            $res->unsetCookie("testCookie");
        }

    }

?>