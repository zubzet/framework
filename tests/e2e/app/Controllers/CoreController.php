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

        public function action_throwsException(Request $req, Response $res) {
            throw new \RuntimeException("regression-controller-exception-marker");
        }

        public function action_triggersDeprecation(Request $req, Response $res) {
            trigger_error("regression-controller-deprecation-marker", E_USER_DEPRECATED);
            echo "deprecation was not promoted";
        }

        public function action_command(Request $req, Response $res) {
            echo json_encode($req->getParameters());
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

        public function action_renderemptylayout(Request $req, Response $res) {
            return $res->render("core/empty", [], "layout/min_layout");
        }

        public function action_e2e_superpermission(Request  $req, Response $res) {
            $checkSuperPermission = $req->checkSuperPermission("core.superpermission", true);
            $checkPermission = $req->checkPermission("core.superpermission", true);

            echo json_encode([
                "checkSuperPerm" => $checkSuperPermission,
                "checkPerm" => $checkPermission
             ]);
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

        // Action which prints arguments
        public function TestRoute_WithArguments(Request $req, Response $res, $arg1 = null, $arg2 = null) {
            print_r("TestRoute Executed");
            print_r($req->getRouteParameter());
            echo " Args: $arg1 $arg2";
        }

        // Middleware which prints and accepts
        public function Route_Middleware_Accept_WithArguments(Request $req, Response $res, $arg1 = null, $arg2 = null) {
            print_r("Route Middleware Accept Executed");
            print_r($req->getRouteParameter());
            echo " Args: $arg1 $arg2";
            return true;
        }

        // Afterware which prints arguments
        public function Route_Afterware_WithArguments(Request $req, Response $res, $arg1 = null, $arg2 = null) {
            print_r("Route Afterware Executed");
            print_r($req->getRouteParameter());
            echo " Args: $arg1 $arg2";
        }
        


        /**
         * Testing the Query Builder
         */
        public function action_querybuilderSelectWhereExtended(Request $req, Response $res) {
            echo json_encode(($req->getModel("QueryBuilder")->selectUserExtended()));
        }

        public function action_querybuilderSelectJoin(Request $req, Response $res) {
            echo json_encode($req->getModel("QueryBuilder")->selectUserJoin());
        }

        public function action_querybuilderSelectLike(Request $req, Response $res) {
            echo json_encode($req->getModel("QueryBuilder")->selectUserLike()); 
        }

        public function action_querybuilderSelectLT(Request $req, Response $res) {
            echo json_encode($req->getModel("QueryBuilder")->selectUserLT());
        }

        public function action_querybuilderSelectIn(Request $req, Response $res) {
            echo json_encode($req->getModel("QueryBuilder")->selectUserIn());
        }

        public function action_querybuilderSelectORAND(Request $req, Response $res) {
            echo json_encode($req->getModel("QueryBuilder")->selectUserORAND());
        }

        public function action_querybuilderSelectLimit(Request $req, Response $res) {
            echo json_encode($req->getModel("QueryBuilder")->selectUserLimit());

        }

        public function action_queryBuilderInsert(Request $req, Response $res) {
            $req->getModel("QueryBuilder")->insert();


            echo json_encode(
                model("QueryBuilder")->selectInsert()
            );
        }

        public function action_queryBuilderUpdate(Request $req, Response $res) {
            $req->getModel("QueryBuilder")->updateLanguage();

            echo json_encode($req->getModel("QueryBuilder")->selectLanguageById(1));
        }

        public function action_queryBuilderDelete(Request $req, Response $res) {
            $req->getModel("QueryBuilder")->deleteLanguage();

            echo json_encode([
                "null" => $req->getModel("QueryBuilder")->selectLanguageById(1) == null ? "null" : "not null"
            ]);
        }

        public function action_queryBuilderCakePHPCompat(Request $req, Response $res) {
            $checks = [];

            $checks['class_exists'] = class_exists(\Cake\Database\ValueBinder::class);

            if($checks['class_exists']) {
                $reflection = new \ReflectionClass(\Cake\Database\ValueBinder::class);

                $checks['method_placeholder'] = $reflection->hasMethod('placeholder');
                $checks['method_bind'] = $reflection->hasMethod('bind');
                $checks['method_generateManyNamed'] = $reflection->hasMethod('generateManyNamed');
                $checks['method_bindings'] = $reflection->hasMethod('bindings');

                $checks['property__bindings'] = $reflection->hasProperty('_bindings');

                if($checks['method_placeholder']) {
                    $m = $reflection->getMethod('placeholder');
                    $params = $m->getParameters();
                    $checks['placeholder_param_count_1'] = count($params) === 1;
                    $checks['placeholder_param_is_token'] = isset($params[0]) && $params[0]->getName() === 'token';
                    $returnType = $m->getReturnType();
                    $checks['placeholder_returns_string'] = $returnType !== null && (string)$returnType === 'string';
                }

                // bind($param, $value, $type = null): void — at least 2 params
                if($checks['method_bind']) {
                    $m = $reflection->getMethod('bind');
                    $params = $m->getParameters();
                    $checks['bind_has_at_least_2_params'] = count($params) >= 2;
                }

                // generateManyNamed(iterable $values, $type = null): array
                if($checks['method_generateManyNamed']) {
                    $m = $reflection->getMethod('generateManyNamed');
                    $params = $m->getParameters();
                    $checks['generateManyNamed_has_values_param'] = isset($params[0]) && $params[0]->getName() === 'values';
                    $returnType = $m->getReturnType();
                    $checks['generateManyNamed_returns_array'] = $returnType !== null && (string)$returnType === 'array';
                }

                // bindings(): array
                if($checks['method_bindings']) {
                    $m = $reflection->getMethod('bindings');
                    $returnType = $m->getReturnType();
                    $checks['bindings_returns_array'] = $returnType !== null && (string)$returnType === 'array';
                }
            }

            $compatible = !in_array(false, $checks, true);

            echo json_encode([
                'compatible' => $compatible,
                'checks' => $checks,
            ]);
        }

        public function action_queryBuilderInjectionTest(Request $req, Response $res) {
            $result = $req->getModel("QueryBuilder")->injectionTest();
            echo json_encode([
                'count' => count($result ?? []),
                'result' => $result ?? [],
            ]);
        }

        public function action_sendemail_static(Request $req, Response $res) {
            $res->sendEmail("test@zierhut-it.de", "This is a Test Email Static", "email/Static", "en", [], "layout/email_layout");
        }

        public function action_sendemail_static_mail_layout(Request $req, Response $res) {
            $res->sendEmail("test@zierhut-it.de", "This is a Test Email Static", "email/Static", "en", [], "mail");
        }

        public function action_sendemail_static_mail_layout_path(Request $req, Response $res) {
            $res->sendEmail("test@zierhut-it.de", "This is a Test Email Static", "email/Static", "en", [], "layout/mail_layout.php");
        }

        public function action_sendemail_dynamic(Request $req, Response $res) {
            $res->sendEmail("test@zierhut-it.de", "This is a Test Email Dynamic", "email/Dynamic", "en", [
                "test_data" => "Test Data 1", 
                "test_data2" => "Test Data 2"
            ], "email");
        }

        public function action_sendemailtouser_static(Request $req, Response $res) {
            $res->sendEmailToUser(1, "This is a Test Email Static", "email/Static", [], "email");
        }

        public function action_bigintFileSize(Request $req, Response $res) {
            echo json_encode($req->getModel("Core")->insertLargeFile());
        }

        public function action_sendemailtouser_dynamic(Request $req, Response $res) {
            $res->sendEmailToUser(1, "This is a Test Email Dynamic", "email/Dynamic", [
                "test_data" => "Test Data 1", 
                "test_data2" => "Test Data 2"
            ], "email");
        }

    }

?>