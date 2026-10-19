<?php

namespace ZubZet\Framework\Authentication;

use DateTime;
use ZubZet\Framework\Authentication\Permission\User;
use ZubZet\Framework\Logger\LogEventType;
use ZubZet\Framework\Logger\Logger;

class Login {

    // Records a failed login so it counts toward the rate limit
    public static function recordTry(User $user): void {
        model("z_login")->newLoginTry($user->id());
    }

    // False when the account saw too many recent tries; unless $boolResult, the owner is warned once per window
    public static function checkLoginTries(User $user, bool $boolResult = false): bool {
        $timespanStart = (new DateTime())->setTimestamp((int) strtotime('-' . config("maxLoginTriesTimespan")));
        $recentTries = (int) model("z_login")->countLoginTriesByTimeSpan($user->id(), $timespanStart->format("Y-m-d H:i:s"));

        // If the user has not exceeded the max tries, return true
        if($recentTries <= config("maxLoginTriesPerTimespan")) return true;

        // If the developer only wants to know if the user is blocked, return false without sending an email or logging
        if($boolResult) return false;

        // Only warn for a valid client IP
        $clientIp = filter_var(request()->ip(), FILTER_VALIDATE_IP);
        if($clientIp && model("z_login")->sendTooManyLoginsEmailByUserId($user->id())) {
            response()->sendEmailToUser(
                $user->id(),
                [
                    "DE_Formal" => "Sicherheitsmeldung",
                    "en" => "Security alert",
                ],
                "email_too_many_logins.php",
                [
                    "user" => $user->getAll(),
                    "date" => date("Y-m-d H:i:s"),
                    "ip" => $clientIp,
                ],
            );
        }

        model("z_login")->addTooManyLoginsEmailByUserId($user->id());

        logger(Logger::ZUBZET)->warning(LogEventType::ACCOUNT_LOGIN_RATE_LIMITED, [
            "userId" => $user->id(),
        ]);

        return false;
    }
}
