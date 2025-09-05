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


        /**
         * Testing the Routing System with Middleware
         */

        // Default Test Route
        public function TestRoute(Request $req, Response $res) {
            print_r("TestRoute Executed");
            print_r($req->getRouteParameter());
        }

        // Middleware for Routes which let the request pass
        public function Route_Middleware_Accept(Request $req, Response $res) {
            print_r("Route Middleware Accept Executed");
            print_r($req->getRouteParameter());
            return true;
        }

        // Middleware for Groups which let the request pass
        public function Group_Middleware_Accept(Request $req, Response $res) {
            print_r("Group Middleware Accept Executed");
            print_r($req->getRouteParameter());
            return true;
        }

        // Middleware for Routes which block the request
        public function Route_Middleware_Block(Request $req, Response $res) {
            print_r("Route Middleware Blocked Executed");
            print_r($req->getRouteParameter());
        }

        // Middleware for Groups which block the request
        public function Group_Middleware_Block(Request $req, Response $res) {
            print_r("Group Middleware Blocked Executed");
            print_r($req->getRouteParameter());
        }

        // Afterware for Routes
        public function Route_Afterware(Request $req, Response $res) {
            print_r("Route Afterware Executed");
            print_r($req->getRouteParameter());
        }

        // Afterware for Groups
        public function Group_Afterware(Request $req, Response $res) {
            print_r("Group Afterware Executed");
            print_r($req->getRouteParameter());
        }


        /**
         * Testing the Query Builder
         */

        public function action_querybuilderSelect(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectAllUsers());
        }

        public function action_querybuilderSelectWhere(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserById(1));
        }

        public function action_querybuilderSelectWhereExtended(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserExtended());
        }

        public function action_querybuilderSelectJoin(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserJoin());
        }

        public function action_querybuilderSelectLike(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserLike()); 
        }

        public function action_querybuilderSelectLT(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserLT());
        }

        public function action_querybuilderSelectIn(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserIn());
        }

        public function action_querybuilderSelectORAND(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserORAND());
        }

        public function action_querybuilderSelectLimit(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserLimit());

        }

        public function action_querybuilderSelectOrder(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserOrder());
        }

        public function action_querybuilderSelectGroup(Request $req, Response $res) {
            print_r($req->getModel("QueryBuilder")->selectUserGroup());
        }

        public function action_queryBuilderInsert(Request $req, Response $res) {
            $req->getModel("QueryBuilder")->insertLanguage();

            print_r($req->getModel("QueryBuilder")->selectLanguageById(2));
            print_r($req->getModel("QueryBuilder")->selectLanguageById(3));
            print_r($req->getModel("QueryBuilder")->selectLanguageById(4));
        }

        public function action_queryBuilderUpdate(Request $req, Response $res) {
            $req->getModel("QueryBuilder")->updateLanguage();

            print_r($req->getModel("QueryBuilder")->selectLanguageById(1));
        }

        public function action_queryBuilderDelete(Request $req, Response $res) {
            $req->getModel("QueryBuilder")->deleteLanguage();

            print_r($req->getModel("QueryBuilder")->selectLanguageById(1));
        }
    }

?>