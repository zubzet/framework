<?php

namespace ZubZet\Framework\Authentication;

use ZubZet\Framework\Authentication\Permission\User;

class Session extends AuthenticationObject {

    use CanUseSession;
    use HandleTrait;
    use RetrievalTrait;

    public static array $dbExpression = [
        "zr.is_apikey" => 0
    ];

    public static function add(User $user, ?User $userExec = null, ?string $name = null, ?string $reason = null): Session {
        if(is_null($userExec)) $userExec = $user;

        return model("z_login")->createLoginToken($user->id(), $userExec->id(), $name, $reason);
    }
}
