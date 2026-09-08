<?php

    namespace ZubZet\Framework\Authentication;

    use ZubZet\Framework\Authentication\Permission\User;

    class APIKey extends AuthenticationObject {

        use CanUseSession;
        use HandleTrait;
        use RetrievalTrait;

        public static array $dbExpression = [
            "zr.is_apikey" => 1
        ];

        public static function add(User $user, ?string $name = null, ?string $reason = null): APIKey {
            return model("z_login")->createLoginToken($user->id(), $user->id(), $name, $reason, isApiKey: true);
        }
    }
