<?php

    use ZubZet\Framework\Authentication\APIKey;
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;

    class ZubZetController {

        public function health(Request $req, Response $res) {
            // The buffer discards connection warnings PHP 8.0 emits instead of throwing
            ob_start();
            try {
                db()->exec("SELECT 1");
                $healthy = true;
            } catch(\Throwable) {
                $healthy = false;
            }
            ob_end_clean();

            if(!$healthy) http_response_code(503);
            return $res->json(["status" => $healthy ? "healthy" : "unhealthy"]);
        }

        public function changePassword(Request $req, Response $res) {
            $account = $this->account();
            if(is_null($account)) return $res->error("Not logged in");

            $formResult = $req->validateForm([
                (new FormField("password_current"))->required(),
                (new FormField("password_new"))->required()->length(3, 64),
                (new FormField("password_repeat"))->required(),
            ]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $currentPassword = $req->getPost("password_current");
            $newPassword = $req->getPost("password_new");

            // An array passes the length rule by its item count and would only
            // fail once it reaches the hashing, so it is turned away here
            if(!is_string($currentPassword) || !is_string($newPassword)) {
                return $res->error("Invalid input");
            }

            if($newPassword !== $req->getPost("password_repeat")) {
                $formResult->addCustomError("password_repeat", "password_mismatch");
                return $res->formErrors($formResult->errors);
            }

            if(!$account->verifyPassword($currentPassword)) {
                $formResult->addCustomError("password_current", "password_wrong");
                return $res->formErrors($formResult->errors);
            }

            $account->updatePassword($newPassword);

            logger(Logger::ZUBZET)->info(LogEventType::PASSWORD_RESET, [
                "userId" => $account->id(),
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
            return $this->revokeToken(Session::byUuid($this->postText($req, "uuid", 36)), $res);
        }

        public function renameSession(Request $req, Response $res) {
            $session = Session::byUuid($this->postText($req, "uuid", 36));
            if(!$this->ownsToken($session)) return $res->error("Unknown token");

            $name = $this->postText($req, "name", 255);
            $session->setName(empty($name) ? null : $name);

            return $res->success();
        }

        public function revokeApiKey(Request $req, Response $res) {
            return $this->revokeToken(APIKey::byUuid($this->postText($req, "uuid", 36)), $res);
        }

        public function createApiKey(Request $req, Response $res) {
            $account = $this->account();
            if(is_null($account)) return $res->error("Not logged in");

            $name = $this->postText($req, "name", 255);
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

        // Client input arrives as mixed, so a text field is cut to what its column takes
        private function postText(Request $req, string $key, int $maxLength): string {
            $value = $req->getPost($key, "");

            return is_string($value) ? mb_substr(trim($value), 0, $maxLength) : "";
        }

        private function revokeToken(Session|APIKey|null $token, Response $res) {
            if(!$this->ownsToken($token)) return $res->error("Unknown token");

            $token->invalidate();

            return $res->success();
        }

        // An unknown uuid and somebody else's are both not owned, so neither can be probed
        private function ownsToken(Session|APIKey|null $token): bool {
            return !is_null($token) && $token->userId() === user()->userId;
        }

    }

?>
