<?php

    use ZubZet\Framework\Authentication\APIKey;
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;

    class ProfileController extends z_controller {

        public function action_index(Request $req, Response $res) {
            $account = $this->account();
            if(is_null($account)) return $res->reroute(["login"]);

            return $res->render("profile.php", [
                "title" => "Profile",
                "account" => $account,
            ]);
        }

        public function changePassword(Request $req, Response $res) {
            $account = $this->account();
            if(is_null($account)) return $res->error("Not logged in");

            $newPassword = $req->getPost("password_new");

            $formResult = $req->validateForm([
                (new FormField("password_current"))->required(),
                (new FormField("password_new"))->required()->length(3, 64),
                (new FormField("password_repeat"))->required(),
            ]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            if($newPassword !== $req->getPost("password_repeat")) {
                $formResult->addCustomError("password_repeat", "password_mismatch");
                return $res->formErrors($formResult->errors);
            }

            if(!$account->verifyPassword($req->getPost("password_current"))) {
                $formResult->addCustomError("password_current", "password_wrong");
                return $res->formErrors($formResult->errors);
            }

            $account->updatePassword($newPassword);

            logger(Logger::ZUBZET)->info(LogEventType::PASSWORD_RESET, [
                "userId" => user()->userId,
                "reason" => "change",
            ]);

            return $res->success();
        }

        public function clearSessions(Request $req, Response $res) {
            $account = $this->account();
            if(is_null($account)) return $res->error("Not logged in");

            Session::clearForUser($account);

            return $res->success();
        }

        public function revokeSession(Request $req, Response $res) {
            return $this->revoke(Session::byUuid($req->getPost("uuid", "")), $res);
        }

        public function renameSession(Request $req, Response $res) {
            $session = Session::byUuid($req->getPost("uuid", ""));
            if(!$this->owns($session)) return $res->error("Unknown token");

            $name = trim($req->getPost("name", ""));
            $session->setName(empty($name) ? null : $name);

            return $res->success();
        }

        public function revokeApiKey(Request $req, Response $res) {
            return $this->revoke(APIKey::byUuid($req->getPost("uuid", "")), $res);
        }

        public function createApiKey(Request $req, Response $res) {
            $account = $this->account();
            if(is_null($account)) return $res->error("Not logged in");

            $name = trim($req->getPost("name", ""));
            $key = APIKey::add($account, name: empty($name) ? null : $name);

            $lifetime = $req->getPost("lifetime");
            if($lifetime === "never") {
                $key->setCanExpire(false);
            } else {
                // Only offered lifetimes are accepted, so no client input reaches DateTime
                $days = array_key_exists((int) $lifetime, APIKey::LIFETIMES)
                    ? (int) $lifetime
                    : array_key_first(APIKey::LIFETIMES);

                // Anchored to the database-written created, since PHP may run on another timezone
                $key->setExpiresAt(new DateTime("{$key->created()} +{$days} days"));
            }

            // The single moment the token is readable to its owner
            return $res->success(["token" => $key->token()]);
        }

        // The signed in account, or null when the login has no account behind it
        private function account(): ?User {
            return user()->isLoggedIn ? User::byId(user()->userId) : null;
        }

        private function revoke(Session|APIKey|null $token, Response $res) {
            if(!$this->owns($token)) return $res->error("Unknown token");

            $token->invalidate();

            return $res->success();
        }

        // An unknown uuid and somebody else's are both not owned, so neither can be probed
        private function owns(Session|APIKey|null $token): bool {
            return !is_null($token) && $token->userId() === user()->userId;
        }

    }

?>
