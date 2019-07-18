<?php 
    class LoginController {

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

        public static $action_index_sitemap = true;
        public function action_index($req, $res) {

            //Check if user has entert information
            if ($req->getPost("name", false) !== false) {

                //Find an user
                $user = $req->getModel("z_login", $req->getZRoot())->getUserByLogin($req->getPost("name"));
                
                //Username not found
                if ($user === false) {
                    die("Username or password is wrong");
                }
                
                //Password handler import
                require_once $req->getZRoot().'z_libs/passwordHandler.php';
        
                //REGISTER
                $bypass = false;
                if ($user["password"] == NULL) {
                    die("Your account is not activated yet. If you can not find the activation mail, use the forgot password function.");
                }
                
                //Max login tries
                if ($req->getModel("z_login", $req->getZRoot())->countLoginTriesByTimeSpan($user["id"], date('Y-m-d H:i:s', strtotime('-'.$req->getBooterSettings("maxLoginTriesTimespan")))) > $req->getBooterSettings("maxLoginTriesPerTimespan")) {

                    /* SECURITY EMAIL */
                    if ($req->getModel("z_login")->sendTooManyLoginsEmailByUserId($user["id"])) {
                        
                        $ip = /*$this->get_client_ip_env() =*/ "178.9.171.91";

                        if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, ["127.0.0.1", "::1"])) {
                            
                            $res->sendEmailToUser(
                                $user["id"],
                                [
                                    "DE_Formal" => "Sicherheitsmeldung",
                                    "en" => "Security alert"
                                ],
                                "email_too_many_logins.php",
                                [
                                    "user" => $user,
                                    "date" => date("Y-m-d H:i:s"),
                                    "ip" => json_decode(file_get_contents("https://api.ipdata.co/".$ip."?api-key=".$req->getBooterSettings("ipdata_co_api_key")))
                                ]
                            );

                        }

                    }

                    $req->getModel("z_login", $req->getZRoot())->addTooManyLoginsEmailByUserId($user["id"]);

                    //Log
                    $req->getModel("z_general")->logActionByCategory("SecurityAlert", "Too many login tries. Account temporarily locked. (user ID: $user[id])", $user["id"]);

                    die("Too many login tries. Try again later.");
                }

                //Check the password
                if (passwordHandler::checkPassword($req->getPost("password"), $user["password"], $user["salt"])) {
                    $res->loginAs($user["id"]);
                    die("successful");
                } else {
                    //Add login try
                    $req->getModel("z_login", $req->getZRoot())->newLoginTry($user["id"]);
                    die("Username or password is wrong");
                }

            } else {
                //else the user wants the form to login
                $res->render("login.php", [
                    "title" => "Login ",
                    "noLayout" => $req->getGet("noLayout", false)
                ], "layout/min_layout.php");
            }
        }

        public function action_logout($req, $res) {
            $res->logout();
        }
        
        public static $action_forgot_password_sitemap = true;
        public function action_forgot_password($req, $res) {

            $action = $req->getParameters(0, 1);
            if ($action == "check") {

                $user = $req->getModel("z_login")->getUserByLogin($req->getPost("unameemail"));

                if (isset($user["id"])) {

                    $code = $req->getModel("z_login", $req->getZRoot())->addResetCode(
                        $user["id"],
                        $req->getModel("z_general")->getUniqueRef(),
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
                            "reset_link" => $url
                        ]
                    );

                    //Log
                    $req->getModel("z_general")->logActionByCategory("PasswordResetRequest", "Password reset requested for " . $user["email"], $user["id"]);
                }

                $res->generateRest([
                    "result" => isset($user["id"]) ? "success" : "error"
                ]);
            }

            $res->render("login_forgotpassword.php", [
                "title" => "Forgot password ",
                "noLayout" => $req->getGet("noLayout", false)
            ], "layout/min_layout.php");
        }

        public function action_reset($req, $res) {
            
            $code = $req->getParameters(0, 1);
            $DBResetCode = $req->getModel("z_login", $req->getZRoot())->getResetCode($code, $req->getBooterSettings("forgotPasswordTimeSpan"));

            if (!$DBResetCode) die("ERROR: This code is not or no longer valid!");

            if ($req->getPost("password", false) !== false) {
                
                //Generating a new password
                require_once $req->getZRoot().'z_libs/passwordHandler.php';
                $req->getModel("z_login", $req->getZRoot())->updatePassword($DBResetCode["userId"], passwordHandler::createPassword($req->getPost("password")));
                
                //Update reset code active attribute
                $req->getModel("z_login", $req->getZRoot())->disableResetCode($DBResetCode["id"]);

                //Log of password reset
                $catId = $req->getModel("z_general")->getLogCategoryIdByName("PasswordReset");
                switch ($DBResetCode["reason"]) {
                    case "forgot": $catId = $req->getModel("z_general")->getLogCategoryIdByName("PasswordReset"); break;
                    case "create": $catId = $req->getModel("z_general")->getLogCategoryIdByName("PasswordCreated"); break;
                    case "change": $catId = $req->getModel("z_general")->getLogCategoryIdByName("PasswordChanged"); break;
                }

                $req->getModel("z_general")->logAction($catId, "Password reseted (UserId: $DBResetCode[userId])", $DBResetCode["userId"]);

                //Rerouting back to root
                $res->rerouteUrl();
                
            } else {

                $res->render("login_reset.php", [
                    "title" => "Password reset "
                ], "layout/min_layout.php");

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