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

        return model("z_login")->createLoginToken($user->id(), $userExec->id());
    }

    public static function byToken(string $token): ?Session {
        $result = model("z_login")->getSessionByToken($token);

        if(is_null($result)) return null;

        return new Session($result);
    }

    public static function byUser(User $user): array {
        $result = model("z_login")->getSessionsByUserId($user);

        if(empty($result)) return [];

        $sessions = [];
        foreach($result as $data) {
            $sessions[] = new Session($data);
        }

        return $sessions;
    }

    public function setExtensionTime(int $seconds): void {
        if($this->shouldRefresh) $this->refresh();

        model("z_login")->setExtensionTime($this, $seconds);

        $this->refreshOnNextUse();
    }

    public function extend(int $seconds): void {
        if($this->shouldRefresh) $this->refresh();

        model("z_login")->extendLoginToken($this, $seconds);

        $this->refreshOnNextUse();
    }

    public function invalidate(): void {
        model("z_login")->invalidateSession($this);
        $this->nullId();
    }

    public function isExpired(): bool {
        if($this->shouldRefresh) $this->refresh();

        $lifetime = (int) config("loginTimeoutSeconds", TIMESPAN_DAY_7);

        if(!is_null($this->extendedSeconds())) $lifetime += $this->extendedSeconds();

        return !((strtotime($this->created()) + $lifetime) > time());
    }

    public function token(): ?string {
        return $this->getField("token");
    }

    public function userId(): ?int {
        return $this->getField("userId");
    }

    public function userIdExec(): ?int {
        return $this->getField("userId_exec");
    }

    public function extendedSeconds(): ?int {
        return $this->getField("extended_seconds");
    }

    public function created(): ?string {
        return $this->getField("created");
    }

    private function refreshOnNextUse(): void {
        $this->shouldRefresh = true;
    }
}
