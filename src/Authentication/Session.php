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

    /**
     * When this session last passed a two factor check, or null while it
     * never did
     */
    public function lastTwoFactor(): ?string {
        return $this->getField("last_2fa");
    }

    /**
     * How many wrong two factor codes this session may still send. At zero it
     * is signed out rather than asked again.
     */
    public function remainingTwoFactorTries(): int {
        return (int) $this->getField("remaining_2fa_tries");
    }

    /**
     * Whether this session has to pass two factor again before it is let near
     * a guarded action.
     *
     * False for an account that carries no two factor - there is nothing to
     * renew and the visitor could never satisfy it. Otherwise true while the
     * session never passed a check, or once the last one aged past
     * `two_factor_freshness_seconds`.
     */
    public function requireRenew(): bool {
        $user = User::byId($this->userId());
        if(is_null($user) || !$user->hasTwoFactor()) return false;

        $lastTwoFactor = $this->lastTwoFactor();
        if(is_null($lastTwoFactor)) return true;

        $freshness = configNumeric("two_factor_freshness_seconds", 900);

        return strtotime($lastTwoFactor) <= time() - $freshness;
    }

    // Ends every active login of a user, leaving their api keys alone
    public static function clearForUser(User $user): void {
        model("z_login")->clearSessions($user);
    }
}
