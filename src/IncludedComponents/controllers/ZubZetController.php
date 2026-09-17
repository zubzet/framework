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
            $account = user()->isLoggedIn ? User::byId(user()->userId) : null;
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
            $account = user()->isLoggedIn ? User::byId(user()->userId) : null;
            if(is_null($account)) return $res->error("Not logged in");

            Session::clearForUser($account);

            return $res->success();
        }

        public function renameToken(Request $req, Response $res) {
            $uuid = $req->getPost("uuid", "");
            if(!is_string($uuid)) return $res->error("Unknown token");

            // The kind picks the lookup, and each one only finds its own rows, so
            // a session cannot be addressed as an api key either. A type the form
            // never offers compares equal to nothing and lands on default
            $token = match($req->getPost("type", "")) {
                "session" => Session::byUuid($uuid),
                "api-key" => APIKey::byUuid($uuid),
                default => null,
            };

            // An unknown uuid and somebody else's are both not owned, so neither can be probed
            if(is_null($token) || $token->userId() !== user()->userId) return $res->error("Unknown token");

            // The name is client input and the column takes 255 characters
            $name = $req->getPost("name", "");
            $name = is_string($name) ? mb_substr(trim($name), 0, 255) : "";

            $token->setName(empty($name) ? null : $name);

            return $res->success();
        }

        public function revokeToken(Request $req, Response $res) {
            $uuid = $req->getPost("uuid", "");
            if(!is_string($uuid)) return $res->error("Unknown token");

            $token = match($req->getPost("type", "")) {
                "session" => Session::byUuid($uuid),
                "api-key" => APIKey::byUuid($uuid),
                default => null,
            };

            if(is_null($token) || $token->userId() !== user()->userId) return $res->error("Unknown token");

            $token->invalidate();

            return $res->success();
        }

        public function createApiKey(Request $req, Response $res) {
            $account = user()->isLoggedIn ? User::byId(user()->userId) : null;
            if(is_null($account)) return $res->error("Not logged in");

            // The name is client input and the column takes 255 characters
            $name = $req->getPost("name", "");
            $name = is_string($name) ? mb_substr(trim($name), 0, 255) : "";

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

    }

?>
