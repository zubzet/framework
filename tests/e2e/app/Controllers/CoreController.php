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

        public function action_get(Request $req, Response $res) {
            print_r($req->getGet("TestGet"));
        }

        // Probes the no-key branch of CanRetrieveFromInput::getGet(),
        // which returns the full GET array. Used by core/request.cy.js.
        public function action_getAll(Request $req, Response $res) {
            return $res->json($req->getGet());
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

                // bind($param, $value, $type = null): void - at least 2 params
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

        // Returns the resolved client IP. json_encode preserves a real null
        // (vs flattening it to "") when the method legitimately returns null.
        public function action_clientIp(Request $req, Response $res): void {
            echo json_encode($req->ip());
        }

        // referer / userAgent / getExecutionTime all may return null per the
        // method contract - json_encode keeps that observable in the test.
        public function action_referer(Request $req, Response $res): void {
            echo json_encode($req->referer());
        }

        public function action_userAgent(Request $req, Response $res): void {
            echo json_encode($req->userAgent());
        }

        // Sleeps for ?delay=<ms> when given, then emits Request::getExecutionTime().
        // The test compares a "fast" call to a deliberately delayed one to prove
        // the value tracks wall-clock time since REQUEST_TIME_FLOAT.
        public function action_executionTime(Request $req, Response $res): void {
            $delayMs = (int)$req->getGet("delay", 0);
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
            echo json_encode($req->getExecutionTime());
        }

        // getCurrentURL / getDomain may mutate the framework's configured host
        // via DynamicAttributes (zubzet()->host = ...). HasDynamicAttributes
        // routes that through __set, and config("host") reads the new value
        // back via __get on the same request. The override lasts one request -
        // the INI re-loads on the next boot.
        public function action_currentUrl(Request $req, Response $res): void {
            $hostOverride = $req->getGet("hostOverride");
            if ($hostOverride !== null && $hostOverride !== "") {
                zubzet()->host = $hostOverride;
            }
            echo $req->getCurrentURL();
        }

        public function action_domain(Request $req, Response $res): void {
            $hostOverride = $req->getGet("hostOverride");
            if ($hostOverride !== null && $hostOverride !== "") {
                zubzet()->host = $hostOverride;
            }
            echo $req->getDomain();
        }

        // `/Core/readable[/skip…]/<slug>?offset=<n>` → JSON {id, text} per
        // getReadableParameter's last-hyphen split rule. Offset is passed
        // through so the test covers both 0 and non-zero offsets.
        public function action_readable(Request $req, Response $res): void {
            $offset = (int)$req->getGet("offset", 0);
            echo json_encode($req->getReadableParameter($offset));
        }

        // Probes for Request::checkPermission, covered by:
        //   advanced/command.cy.js         - "console" + boolResult branch
        //   core/permissions.cy.js         - !isLoggedIn + boolResult branch
        //
        // consoleBool exercises checkPermission("console", boolResult: true)
        // over HTTP - the branch that returns false without exit so the
        // action can keep running and echo our marker.
        public function action_consoleBool(Request $req, Response $res): void {
            echo $req->checkPermission("console", boolResult: true) ? "allowed" : "denied";
        }

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

        // Round-trips getBody() + getJson(). The JSON parse uses
        // JSON_THROW_ON_ERROR; we catch it here so the test can assert both
        // the happy path and the malformed-body path within one request.
        public function action_requestBody(Request $req, Response $res): void {
            $body = $req->getBody();
            $json = null;
            $jsonError = null;
            try {
                $json = $req->getJson();
            } catch (\JsonException $e) {
                $jsonError = $e->getMessage();
            }
            echo json_encode([
                'body'      => $body,
                'json'      => $json,
                'jsonError' => $jsonError,
            ]);
        }
    }

?>