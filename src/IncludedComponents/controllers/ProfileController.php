<?php

    use ZubZet\Framework\Authentication\APIKey;
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;

    class ProfileController extends z_controller {

        // Available Api key lifetimes in days
        private const API_KEY_LIFETIMES = [
            1 => "1 day",
            3 => "3 days",
            7 => "7 days",
            30 => "30 days",
            60 => "60 days",
            90 => "90 days",
            365 => "1 year",
        ];

        public function action_index(Request $req, Response $res) {
            $account = user()->isLoggedIn ? User::byId(user()->userId) : null;
            if(is_null($account)) return $res->reroute(["login"]);

            if($req->hasFormData("password")) {
                $currentPassword = $req->getPost("password_current");
                $newPassword = $req->getPost("password_new");
                $repeatedPassword = $req->getPost("password_repeat");

                $formResult = $req->validateForm([
                    (new FormField("password_current"))->required(),
                    (new FormField("password_new"))->required()->length(3, 64),
                    (new FormField("password_repeat"))->required(),
                ]);

                if($formResult->hasErrors) return $res->formErrors($formResult->errors);

                if($newPassword !== $repeatedPassword) {
                    $formResult->addCustomError("password_repeat", "password_mismatch");
                    return $res->formErrors($formResult->errors);
                }

                if(!$account->verifyPassword($currentPassword)) {
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

            if($req->isAction("revoke-token")) {
                // Only an explicit "api" reaches a key, anything else stays with logins
                $uuid = $req->getPost("uuid", "");
                $token = $req->getPost("type") === "api"
                    ? APIKey::byUuid($uuid)
                    : Session::byUuid($uuid);

                // An unknown uuid and somebody else's get the same answer, so neither can be probed
                if(is_null($token) || $token->userId() !== user()->userId) {
                    return $res->error("Unknown token");
                }

                $token->invalidate();

                return $res->success();
            }

            if($req->isAction("clear-sessions")) {
                Session::clearForUser($account);

                return $res->success();
            }

            if($req->isAction("create-api-key")) {
                $name = trim($req->getPost("name", ""));
                $key = APIKey::add($account, name: empty($name) ? null : $name);

                $lifetime = $req->getPost("lifetime");
                if($lifetime === "never") {
                    $key->setCanExpire(false);
                } else {
                    // Only offered lifetimes are accepted, so no client input reaches DateTime
                    $days = array_key_exists((int) $lifetime, self::API_KEY_LIFETIMES)
                        ? (int) $lifetime
                        : array_key_first(self::API_KEY_LIFETIMES);

                    // Anchored to the database-written created, since PHP may run on another timezone
                    $key->setExpiresAt(new DateTime("{$key->created()} +{$days} days"));
                }

                // The single moment the token is readable to its owner
                return $res->success(["token" => $key->token()]);
            }

            return $res->render("profile.php", [
                "title" => "Profile",
                "account" => $account,
                "sessions" => Session::byUser($account),
                "apiKeys" => APIKey::byUser($account),
                "apiKeyLifetimes" => self::API_KEY_LIFETIMES,
                "currentUuid" => Session::byToken(user()->getSessionToken())?->uuid(),
            ]);
        }

    }

?>
