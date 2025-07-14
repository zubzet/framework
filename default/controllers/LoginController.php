<?php 
    /**
     * This file holds the login controller
     */

    /**
     * The Login controller handles all login/logout stuff
     */
    class LoginController extends z_controller {

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
                    $res->error("Your account is not activated yet. Check your mails or click <a href='$link'>here</a> to resend the activation.");
                }
                
                //Max login tries
                if ($req->getModel("z_login", $req->getZRoot())->countLoginTriesByTimeSpan($user["id"], date('Y-m-d H:i:s', strtotime('-'.$req->getBooterSettings("maxLoginTriesTimespan")))) > $req->getBooterSettings("maxLoginTriesPerTimespan")) {

                    /* SECURITY EMAIL */
                    if ($req->getModel("z_login")->sendTooManyLoginsEmailByUserId($user["id"])) {
                        
                        $ip = $req->ip();

                        if (filter_var($ip, FILTER_VALIDATE_IP) /*&& !in_array($ip, ["127.0.0.1", "::1"])*/) {
                            
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
                                    "ip" => $ip
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
        public function action_logout($req, Response $res) {
            $res->logout();
            $reroute = $req->getGet("reroute", false);
            if($reroute) {
                $res->rerouteUrl($reroute == "index" ? "" : $reroute);
            }
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
                    (new FormField("email"))
                        -> required()
                        -> filter(FILTER_VALIDATE_EMAIL)
                        -> unique("z_user", "email"),
                    (new FormField("password"))
                        -> required()
                        -> length(3, 64),
                ]);

                if ($formResult->hasErrors) return $res->error(
                    "This email is not allowed!",
                );

                // Create the new user account
                require_once $req->getZRoot().'z_libs/passwordHandler.php';
                $userId =  $req->getModel("z_user")->add(
                    $req->getPost("email"),
                    0,
                    $req->getPost("password"),
                );

                // Return if the user was not created
                if(empty($userId) || !is_numeric($userId)) {
                    return $res->error();
                }

                // Allow for two types of accounts
                $userRoleType = $req->getPost("userRoleType", "");
                if(!in_array($userRoleType, ["", "Secondary"])) $userRoleType = "";

                // Read the config parameter
                $configKey = "registerRoleId".$userRoleType;
                $newUserRoleId = $req->getBooterSettings($configKey);

                // Assign the role if an initial role was defined
                if(!empty($newUserRoleId) && is_numeric($newUserRoleId)) {
                    $req->getModel("z_user")->addRoleToUserByRoleId(
                        $userId,
                        (int) $newUserRoleId,
                    );
                }

                // Send the verification mail
                $this->send_verify_mail($req, $res, $userId);
                return $res->success();
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

            if ($req->getParameters(0, 1) == "check" || $req->isAction("forgot_password")) {

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
                            "en" => "Password Reset",
                            "DE_Formal" => "Passwort Zurücksetzten"
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

            if (isset($_POST["email"])) {
                $user = $model->getUserByEmail($_POST["email"]);

                if (!empty($user)) {
                    $this->send_verify_mail($req, $res, $user["id"]);
                }

                $res->render("login_verify_wait.php", [
                    "title" => "Email verification",
                ], "layout/min_layout.php");
            } else {
                $res->render("login_verify.php", [
                    "title" => "Email verification",
                    "success" => $success,
                    //TODO: Needs to be edited
                    "login" => "index"
                ], "layout/min_layout.php");
            }

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
        public function action_change_password(Request $req, Response $res) {
            $res->reroute(["login", "reset"]);
        }

        private function send_verify_mail(Request $req, Response $res, $userId) {
            $userModel = $req->getModel("z_user");

            $token = $userModel->createVerifyToken($userId);
            $url = $res->booter->root . "login/verify/" . $token;
            $res->sendEmailToUser(
                $userId,
                $req->getBooterSettings("pageName", "")." - Sign Up", 
                "email_verify.php", 
                [
                    "url" => $url
                ]
            );
        }

    }

?>
