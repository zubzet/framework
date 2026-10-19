<?php

namespace ZubZet\Framework\Authentication;

use DateTime;
use ZubZet\Framework\Authentication\Permission\User;

class Login {

    // Records a failed login so it counts toward the rate limit
    public static function recordTry(User $user): void {
        model("z_login")->newLoginTry($user->id());
    }

    public static function countTriesSince(User $user, DateTime $since): int {
        return (int) model("z_login")->countLoginTriesByTimeSpan($user->id(), $since->format("Y-m-d H:i:s"));
    }

    // True when the owner was not warned about too many tries in the last ten minutes
    public static function isTooManyTriesMailDue(User $user): bool {
        return model("z_login")->sendTooManyLoginsEmailByUserId($user->id());
    }

    public static function recordTooManyTriesMail(User $user): void {
        model("z_login")->addTooManyLoginsEmailByUserId($user->id());
    }
}
