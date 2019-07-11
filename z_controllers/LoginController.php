<?php 
    class LoginController {

        public static $permissionLevel = -1;

        private function get_client_ip_env() {
            $ipaddress = '';
            if (getenv('HTTP_CLIENT_IP'))
                $ipaddress = getenv('HTTP_CLIENT_IP');
            else if(getenv('HTTP_X_FORWARDED_FOR'))
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
            else if(getenv('HTTP_X_FORWARDED'))
                $ipaddress = getenv('HTTP_X_FORWARDED');
            else if(getenv('HTTP_FORWARDED_FOR'))
                $ipaddress = getenv('HTTP_FORWARDED_FOR');
            else if(getenv('HTTP_FORWARDED'))
                $ipaddress = getenv('HTTP_FORWARDED');
            else if(getenv('REMOTE_ADDR'))
                $ipaddress = getenv('REMOTE_ADDR');
            else
                $ipaddress = 'UNKNOWN';
        
            return $ipaddress;
        }

        public function action_index($req, $res) {

            //Check if user has entert information
            if ($req->getPost("name", false) !== false) {

                //Find an employee
                $employee = $req->getModel("z_login", $req->getZRoot())->getUserByLogin($req->getPost("name"));
                
                //USername not found
                if ($employee === false) {
                    die("Username or password is wrong");
                }
                
                //Password handler import
                require_once $req->getZRoot().'z_libs/passwordHandler.php';
        
                //REGISTER
                $bypass = false;
                if ($employee["password"] == NULL) {
                    die("Your account is not activated yet. If you can not find the activation mail, use the forgot password function.");
                }
                
                //Max login tries
                if ($req->getModel("z_login", $req->getZRoot())->countLoginTriesByTimeSpan($employee["id"], date('Y-m-d H:i:s', strtotime('-'.$req->getBooterSettings("maxLoginTriesTimespan")))) > $req->getBooterSettings("maxLoginTriesPerTimespan")) {

                    /* SECURITY EMAIL */
                    if ($req->getModel("z_login", $req->getZRoot())->sendTooManyLoginsEmailBYEmployeeId($employee["id"])) {
                        
                        $ip = /*$this->get_client_ip_env() =*/ "178.9.171.91";

                        if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, ["127.0.0.1", "::1"])) {
                            
                            $res->sendEmailToUser(
                                $employee["id"],
                                [
                                    "DE_Formal" => "Sicherheitsmeldung",
                                    "en" => "Security alert"
                                ],
                                "email_too_many_logins.php",
                                [
                                    "employee" => $employee,
                                    "date" => date("Y-m-d H:i:s"),
                                    "ip" => json_decode(file_get_contents("https://api.ipdata.co/".$ip."?api-key=".$req->getBooterSettings("ipdata_co_api_key")))
                                ]
                            );

                        }

                    }

                    $req->getModel("z_login", $req->getZRoot())->addTooManyLoginsEmailBYEmployeeId($employee["id"]);

                    //Log
                    $catId = $req->getModel("General")->getLogCategoryIdByName("SecurityAlert");
                    $req->getModel("General")->logAction($catId, "Too many login tries. Account temporarily locked. (Employee ID: $employee[id])", $employee["id"]);

                    die("Too many login tries. Try again later.");
                }

                //Check the password
                if (passwordHandler::checkPassword($req->getPost("password"), $employee["password"], $employee["salt"])) {
                    $res->loginAs($employee["id"]);
                    die("successful");
                } else {
                    //Add login try
                    $req->getModel("z_login", $req->getZRoot())->newLoginTry($employee["id"]);
                    die("Username or password is wrong");
                }

            } else {
                //else the user wants the form to login
                $layout = $req->getGet("noLayout", false) === "true" ? "wrapper" : "default";
                $res->render("login.php", [
                    "title" => "Login - Skill-DB ACOPA",
                    "noLayout" => $req->getGet("noLayout", false)
                ], $layout);
            }
        }

        public function action_logout($req, $res) {
            //Log (Commented away, because user is not avaiblaablalbe with permissionLevel -1)
            /*
            $user = $req->getRequestingUser();
            print_r($user);exit;
            $catId = $req->getModel("General")->getLogCategoryIdByName("logout");
            $req->getModel("General")->logAction($catId, "User logged out", $user["name"].$user["firstName"]);
            */

            $res->unsetCookie("skdb_login_token");
            $res->rerouteUrl();
        }
        
        public function action_forgot_password($req, $res) {

            $action = $req->getParameters(0, 1);
            if ($action == "check") {

                $user = $req->getModel("z_login", $req->getZRoot())->findAccount($req->getPost("unameemail"));

                if (isset($user["id"])) {

                    $code = $req->getModel("z_login", $req->getZRoot())->addResetCode(
                        $user["id"],
                        $req->getModel("General")->getUniqueRef(),
                        "forgot"
                    );

                    $url = $req->getBooterSettings("host") . $req->getRootFolder() . "login/reset/$code/";
 
                    $res->sendEmailToUser(
                        $user["id"],
                        [
                            "en" => "SKDB Password Reset",
                            "DE_Formal" => "SKD Passwort Zurücksetzten"
                        ],
                        "email_password_reset.php", 
                        [
                            "firstName" => $user["firstName"],
                            "reset_link" => $url
                        ]
                    );

                    //Log
                    $catId = $req->getModel("General")->getLogCategoryIdByName("PasswordResetRequest");
                    $req->getModel("General")->logAction($catId, "Password reset requested for " . $user["name"] . '.' . $user["firstName"], "");
                }

                $res->generateRest([
                    "result" => isset($user["id"]) ? "success" : "error"
                ]);
            }

            $layout = $req->getGet("noLayout", false) === "true" ? "wrapper" : "default";
            $res->render("login_forgotpassword.php", [
                "title" => "Forgot password - Skill-DB ACOPA",
                "noLayout" => $req->getGet("noLayout", false)
            ], $layout);
        }

        public function action_reset($req, $res) {
            
            $code = $req->getParameters(0, 1);
            $DBResetCode = $req->getModel("z_login", $req->getZRoot())->getResetCode($code, $req->getBooterSettings("forgotPasswordTimeSpan"));

            if (!$DBResetCode) die("ERROR: This code is not or no longer valid!");

            if ($req->getPost("password", false) !== false) {
                
                //Generating a new password
                require_once $req->getZRoot().'z_libs/passwordHandler.php';
                $req->getModel("z_login", $req->getZRoot())->updatePassword($DBResetCode["employeeId"], passwordHandler::createPassword($req->getPost("password")));
                
                //Update reset code active attribute
                $req->getModel("z_login", $req->getZRoot())->disableResetCode($DBResetCode["id"]);

                //Log of password reset
                $catId = $req->getModel("General")->getLogCategoryIdByName("PasswordReset");
                switch ($DBResetCode["reason"]) {
                    case "forgot": $catId = $req->getModel("General")->getLogCategoryIdByName("PasswordReset"); break;
                    case "create": $catId = $req->getModel("General")->getLogCategoryIdByName("PasswordCreated"); break;
                    case "change": $catId = $req->getModel("General")->getLogCategoryIdByName("PasswordChanged"); break;
                }

                $req->getModel("General")->logAction($catId, "Password reseted (UserId: $DBResetCode[employeeId])", $DBResetCode["employeeId"]);

                //Rerouting back to root
                $res->rerouteUrl();
                
            } else {

                $res->render("login_reset.php", [
                    "title" => "Password reset - Skill-DB ACOPA"
                ]);

            }
        }

        //Alias for newbies
        public function action_create_password($req, $res) {
            $res->reroute(["login", "reset"]);
        }

        //For later account settings use
        public function action_change_password($req, $res) {
            $res->reroute(["login", "reset"]);
        }

    }

?>