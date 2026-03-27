<?php

namespace ZubZet\Framework\Authentication;

use ZubZet\Framework\Authentication\Permission\User;

class Session extends AuthenticationObject {

    use HandleTrait;
    use RetrievalTrait;

    public static string $dbTable = "z_logintoken";
    public static array $dbExpression = [];

    private bool $shouldRefresh = false;


    public function __construct(array $data) {
        parent::__construct($data);
        $this->loadObject($data);
    }

    public function loadObject(array $data) {
        $this->data = $data;
        $this->setField("userId", $data["userId"]);
        $this->setField("userId_exec", $data["userId_exec"]);
        $this->setField("extended_seconds", $data["extended_seconds"]);
        $this->setField("created", $data["created"]);
    }

    public static function add(User $user, ?User $userExec = null): Session {
        if(is_null($userExec)) $userExec = $user;

        $token = model("z_login")->createLoginToken($user->id(), $userExec->id());

        return Session::byToken($token);
    }

    public static function byToken(string $token): ?Session {
        $result = model("z_login")->getSessionByToken($token);

        if(empty($result)) return null;

        return new Session($result);
    }

    public static function byUser(User $user): array {
        $result = model("z_login")->getSessionsByUserId($user->id());

        if(empty($result)) return [];

        $sessions = [];
        foreach($result as $data) {
            $sessions[] = new Session($data);
        }

        return $sessions;
    }

    public function setExtensionTime(int $seconds): void {
        if($this->shouldRefresh) $this->refresh();

        model("z_login")->setExtensionTime($this->token(), $seconds);

        $this->refreshOnNextUse();
    }

    public function extend(int $seconds): void {
        if($this->shouldRefresh) $this->refresh();

        model("z_login")->extendLoginToken($this->token(), $seconds);

        $this->refreshOnNextUse();
    }

    public function invalidate(): void {
        model("z_login")->invalidateSession($this->token());
        $this->nullId();
    }

    public function isExpired(): bool {
        if($this->shouldRefresh) $this->refresh();

        $lifetime = (int) config("loginTimeoutSeconds", TIMESPAN_DAY_7);

        if(!is_null($this->extendedSeconds())) $lifetime += $this->extendedSeconds();

        return !((strtotime($this->created()) + $lifetime) > time());
    }

    public function token() {
        return $this->getField("token");
    }

    public function userId() {
        return $this->getField("userId");
    }

    public function userIdExec() {
        return $this->getField("userId_exec");
    }

    public function extendedSeconds() {
        return $this->getField("extended_seconds");
    }

    public function created() {
        return $this->getField("created");
    }

    private function refreshOnNextUse(): void {
        $this->shouldRefresh = true;
    }
}
