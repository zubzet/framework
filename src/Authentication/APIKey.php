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

        // Lifetimes an api key can be created with, in days
        public const LIFETIMES = [
            1 => "1 day",
            3 => "3 days",
            7 => "7 days",
            30 => "30 days",
            60 => "60 days",
            90 => "90 days",
            365 => "1 year",
        ];

        public static function add(User $user, ?string $name = null, ?string $reason = null): APIKey {
            return model("z_login")->createLoginToken($user->id(), $user->id(), $name, $reason, isApiKey: true);
        }
    }
