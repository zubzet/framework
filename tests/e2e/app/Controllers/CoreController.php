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

        // Switches the framework to BehaviorOption::NONE (showErrors=0) via
        // ExceptionBehavior::setExceptionBehavior, then throws. The router's
        // exception-catch branch routes to /error/500 because showErrors is
        // 0. Lets exception-handling.cy.js exercise the 500 page without
        // touching z_settings.ini, and gives setExceptionBehavior coverage.
        public function action_throwsExceptionAfterBehaviorNone(Request $req, Response $res) {
            zubzet()->setExceptionBehavior(0);
            throw new \RuntimeException("regression-controller-exception-marker");
        }

        public function action_triggersDeprecation(Request $req, Response $res) {
            trigger_error("regression-controller-deprecation-marker", E_USER_DEPRECATED);
            // @codeCoverageIgnoreStart
            // Unreachable by contract: under BehaviorOption::ALL the
            // trigger_error above is promoted to an ErrorException before
            // we get here. The string is kept as a regression marker - if
            // it ever appears in a response body the test fails loudly,
            // surfacing a broken promotion path.
            echo "deprecation was not promoted";
            // @codeCoverageIgnoreEnd
        }

        // deprecated() raises E_USER_DEPRECATED, which BehaviorOption::ALL promotes like triggersDeprecation above
        public function action_deprecatedHelper(Request $req, Response $res) {
            deprecated("9.9.9", "regression-deprecated-helper-marker");
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
            ], "layout/new_layout");
        }

        public function action_renderemptylayout(Request $req, Response $res) {
            return $res->render("core/empty", [], "layout/min_layout");
        }

        // Renders core/render through the bare-bones layout/empty wrapper -
        // useful when a partial of the page (e.g. a paginated list) is being
        // fetched over AJAX and the caller wants the rendered body only,
        // without surrounding <html>/<head>/<body> chrome. Covered by
        // core/layout.cy.js.
        public function action_renderRaw(Request $req, Response $res) {
            return $res->render("core/render", [
                "data" => "Data",
            ], "layout/empty");
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

        public function action_cookiesset(Request $req, Response $res) {
            $res->setCookie('testCookie', 'cookieValue', time() + 3600, '/', '', false, true);
        }

        // Sets two cookies in one response so core/cookies.cy.js can
        // exercise the getCookies() alias (no-key getCookie branch).
        public function action_cookiessetMulti(Request $req, Response $res) {
            $res->setCookie('cookieA', 'valueA', time() + 3600, '/', '', false, true);
            $res->setCookie('cookieB', 'valueB', time() + 3600, '/', '', false, true);
        }

        public function action_cookieget(Request $req, Response $res) {
            print_r($req->getCookie("testCookie"));
        }

        // Returns every cookie on the request via the getCookies() alias,
        // which delegates to getCookie() with no key.
        public function action_cookiesAll(Request $req, Response $res) {
            return $res->json($req->getCookies());
        }

        public function action_cookieunset(Request $req, Response $res) {
            $res->unsetCookie("testCookie");
        }


        /**
         * Testing the Routing System with Middleware
         */

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
            $req->getModel("QueryBuilder")->update();

            echo json_encode($req->getModel("QueryBuilder")->selectInsertById(1));
        }

        public function action_queryBuilderDelete(Request $req, Response $res) {
            $req->getModel("QueryBuilder")->delete();

            echo json_encode([
                "null" => $req->getModel("QueryBuilder")->selectInsertById(1) == null ? "null" : "not null"
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

        public function action_sendemail_sendername_default(Request $req, Response $res) {
            $res->sendEmail("test@zierhut-it.de", "This is a Test Email Sender Default", "email/Static", "en", [], "email");
        }

        public function action_sendemail_sendername_custom(Request $req, Response $res) {
            $res->sendEmail("test@zierhut-it.de", "This is a Test Email Sender Custom", "email/Static", "en", [], "email", [], "Custom Sender");
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

        // Probes for Request::checkPermission, covered by:
        //   core/permissions.cy.js         - !isLoggedIn + boolResult branch

        // permissionCheck runs both checkPermission shapes back-to-back:
        //   1) boolResult=true   - emits "allowed" / "denied" without exiting.
        //   2) default behavior  - !isLoggedIn redirects, no-permission 403s,
        //                          allowed lets the trailing echo run.
        // Body inspection in the test reveals which branch fired:
        //   - not logged in:  "denied\n" + login page HTML (no "passed" echo)
        //   - logged in OK:   "allowed\ncore.permissions passed"
        public function action_permissionCheck(Request $req, Response $res): void {
            echo $req->checkPermission("core.permissions", boolResult: true) ? "allowed" : "denied";
            echo "\n";
            $req->checkPermission("core.permissions");
            echo "core.permissions passed";
        }
    }

?>