<?php 
    /**
     * This file holds the login controller
     */

    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Translation\Translation;
    use ZubZet\Framework\Security\CSRF;

    /**
     * The Login controller handles all login/logout stuff
     */
    class LoginController extends z_controller {

        // True once the account used up its login tries in the current window.
        // Both the password and the two factor step run through this, so wrong
        // codes count the same as wrong passwords.
        private function blockedByLoginLimit(User $user): bool {
            return !$user->checkLoginTries();
        }

        /**
         * The index action
         * 
         * It will be called when no action is specified
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_index($req, $res) {
            // Only the Z.Request call, which carries the csrf token, submits credentials: render the login form.
            if(!$req->isAction("login")) {
                return $res->render("login.php", [
                    "title" => "Login ",
                    "noLayout" => $req->getGet("noLayout", false),
                ], "layout/min_layout.php");
            }

            // checkPermission() reaches this action internally, past the dispatch check of an opted-out route
            CSRF::verify();

            // Look the account up by the submitted login (its email).
            $user = User::byEmail($req->getPost("name"));

            // Unknown account: reuse the wrong-password message so the response
            // can't be used to probe which emails are registered.
            if(is_null($user)) {
                return $res->error("Username or password is wrong");
            }

            // The account exists but was never activated.
            if(is_null($user->verified())) {
                $link = $req->getRootFolder() . "login/verify";
                return $res->error("Your account is not activated yet. Check your mails or click <a href='$link'>here</a> to resend the activation.");
            }

            if($this->blockedByLoginLimit($user)) {
                return $res->error("Too many login tries. Try again later.");
            }

            // Wrong password: record the attempt so it counts toward the rate limit.
            if(!$user->verifyPassword($req->getPost("password"))) {
                $user->recordLoginTry();
                return $res->error("Username or password is wrong");
            }

            if($user->hasTwoFactor()) {
                $challenge = model("z_login")->createTwoFactorChallenge($user->id());

                return $res->success([
                    "twoFactor" => true,
                    "challenge" => $challenge,
                ]);
            }

            // Correct. verifyPassword() has already self-healed the stored hash if
            // it was stale, so all that is left is to start the session.
            $res->loginAs($user->id());
            return $res->success();
        }


        /**
         * Redeems a two factor challenge and starts the session behind it.
         * Reached over its own route, so the modal can post from any page.
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function twoFactorLogin(Request $req, Response $res) {
            // Only the Z.Request call, which carries the csrf token
            if(!$req->isAction("two-factor-login")) return $res->error("Invalid request");

            $challenge = $req->getPost("challenge");
            if(empty($challenge) || !is_string($challenge)) return $res->error("Missing challenge");

            $challengeObj = model("z_login")->getTwoFactorChallenge($challenge);
            if(is_null($challengeObj)) return $res->error("Invalid challenge");

            $code = $req->getPost("code");
            if(empty($code) || !is_string($code)) return $res->error("Missing code");

            $user = User::byId($challengeObj["userId"]);
            if(is_null($user)) return $res->error("Invalid challenge");

            if($this->blockedByLoginLimit($user)) {
                return $res->error("Too many login tries. Try again later.");
            }

            if(!$user->verifyTwoFactorCode($code)) {
                model("z_login")->newLoginTry($user->id());
                return $res->error("Invalid code");
            }

            model("z_login")->invalidateTwoFactorChallenge($challengeObj["id"]);

            $res->loginAs($user->id(), recordTwoFactor: true);

            return $res->success();
        }

        /**
         * Logs out the requesting user.
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_logout($req, Response $res) {
            return $res->logout();
        }

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
                $userId =  $req->getModel("z_user")->add(
                    $req->getPost("email"),
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
                        null,
                        "forgot"
                    );

                    $url = $req->getBooterSettings("host") . $req->getRootFolder() . "login/reset/$code/";

                    // The recipient's language, not the one of whoever asked
                    $locale = Translation::locale(User::byId($user["id"]));

                    $res->sendEmailToUser(
                        $user["id"],
                        __("email.password_reset.subject", locale: $locale),
                        "email_password_reset.php", 
                        [
                            "reset_link" => $url,
                            "locale" => $locale,
                        ]
                    );

                    //Log
                    logger(Logger::ZUBZET)->info(LogEventType::PASSWORD_RESET_REQUESTED, [
                        "userId" => $user["id"]
                    ]);
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
                $req->getModel("z_login", $req->getZRoot())->updatePassword(
                    User::byId($DBResetCode["userId"]),
                    $req->getPost("password"),
                );

                //Update reset code active attribute
                $req->getModel("z_login", $req->getZRoot())->disableResetCode($DBResetCode["id"]);

                //Log of password reset
                logger(Logger::ZUBZET)->info(LogEventType::PASSWORD_RESET, [
                    "userId" => $DBResetCode["userId"],
                    "reason" => $DBResetCode["reason"]
                ]);

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

            if (!empty($req->getPost("email"))) {
                $user = $model->getUserByEmail($req->getPost("email"));

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
