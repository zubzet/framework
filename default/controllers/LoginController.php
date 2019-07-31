<?php 
    /**
     * This file holds the login controller
     */

    /**
     * The Login controller handles all login/logout stuff
     */
    class LoginController extends z_controller {

        /**
         * Gets the IP adress of the requesting client
         * @return string The Ip Adress of the requesting client. "UNKOWN" when it could not be determined.
         */
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

        /**
         * @var bool $action_index_sitemap If set to true the action_index will appear in the sitemap
         */
        public static $action_index_sitemap = true;

        /**
         * The index action
         * 
         * It will be called when no action is specified
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_index($req, $res) {

            //Check if user has entert information
            if ($req->getPost("name", false) !== false) {

                //Find an user
                $user = $req->getModel("z_login", $req->getZRoot())->getUserByLogin($req->getPost("name"));
                
                //Username not found
                if ($user === false) {
                    $res->error("Username or password is wrong");
                }
                
                //Password handler import
                require_once $req->getZRoot().'z_libs/passwordHandler.php';

                if ($user["verified"] == NULL) {
                    $link = $req->booter->rootFolder . "login/verify";
                    $res->error("Your account is not activated yet. Click <a href='$link'>here</a> to activate it.");
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

                    $res->error("Too many login tries. Try again later.");
                }

                //Check the password
                if (passwordHandler::checkPassword($req->getPost("password"), $user["password"], $user["salt"])) {
                    $res->loginAs($user["id"]);
                    $res->success();
                } else {
                    //Add login try
                    $req->getModel("z_login", $req->getZRoot())->newLoginTry($user["id"]);
                    $res->error("Username or password is wrong");
                }

            } else {
                //else the user wants the form to login
                $res->render("login.php", [
                    "title" => "Login ",
                    "noLayout" => $req->getGet("noLayout", false)
                ], "layout/min_layout.php");
            }
        }

        /**
         * Logs out the requesting user.
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_logout($req, $res) {
            $res->logout();
        }

        /**
         * @var bool $action_register_sitemap If set to true the action_register will appear in the sitemap
         */
        public static $action_register_sitemap = true;

        /**
         * Controls the view for registering as a user
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_signup($req, $res) {
            
            if ($req->isAction("signup")) {

                $formResult = $req->validateForm([
                    (new FormField("email"))      -> required() -> filter(FILTER_VALIDATE_EMAIL) -> unique("z_user", "email"),
                    (new FormField("password"))   -> required() -> length(3, 64)
                ]);

                if ($formResult->hasErrors) {
                    $res->error("There were problems with your input!");
                } else {
                    $userModel = $req->getModel("z_user");
                    require_once $req->getZRoot().'z_libs/passwordHandler.php';

                    $userId = $userModel->add(
                        $req->getPost("email"),
                        0,
                        $req->getPost("password")
                    );
                    
                    if ($userId) {
                        $token = $userModel->createVerifyToken($userId);

                        $url = $res->booter->root . "login/verify/" . $token;
                        $res->sendEmailToUser($userId, "Verify your email!", "email_verify.php", ["url" => $url] ,"layout/email_layout.php");

                        $res->success();
                    } else {
                        $res->error();
                    }
                }
            }

            $res->render("login_signup.php", [], "layout/min_layout.php");
        }
        
        /**
         * @var bool $action_forgot_password_sitemap If set to true the action_forgot_password will appear in the sitemap
         */
        public static $action_forgot_password_sitemap = true;

        /**
         * Serves the forgot password page and resets the a users password
         * @param Request $req The request object
         * @param Response $res The response object
         */
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

        /**
         * Serves the page where the user can reset its password
         * 
         * For access the user needs a code he usally gets per mail. This code will also identify the user so he does not need to be logged in to reset his password
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
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

        /**
         * Used to verify mails
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_verify($req, $res) {
            $code = $req->getParameters(0, 1);
            $model = $req->getModel("z_user");
            $success = $model->verifyUser($code);

            $res->render("login_verify.php", [
                "title" => "Email verification",
                "success" => $success
            ], "layout/min_layout.php");
        }

        /**
         * Redirects the user to the password reset page
         * 
         * This action is used for user who create their password for the first time
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_create_password($req, $res) {
            $res->reroute(["login", "reset"]);
        }

        /**
         * Redirects the user to the password reset page
         * 
         * This action is used for user who wat to change their password but did not forgot it.
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_change_password($req, $res) {
            $res->reroute(["login", "reset"]);
        }

    }

?>