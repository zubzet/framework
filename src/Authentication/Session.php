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
        $this->setField("name", $data["name"]);
        $this->setField("is_permanent", $data["is_permanent"]);
        $this->setField("is_apikey", $data["is_apikey"]);
        $this->setField("extended_seconds", $data["extended_seconds"]);
        $this->setField("created", $data["created"]);
    }

    public static function add(User $user, ?User $userExec = null, ?string $name = null): Session {
        if(is_null($userExec)) $userExec = $user;

        return model("z_login")->createLoginToken($user->id(), $userExec->id(), $name);
    }

    public static function byToken(string $token): ?Session {
        $result = model("z_login")->getSessionByToken($token);

        if(is_null($result)) return null;

        return new Session($result);
    }

    /**
     * All active sessions of a user, optionally narrowed to one kind of session
     *
     * @param User $user The user whose sessions are returned
     * @param ?bool $isApiKey Null returns every session, true only api keys, false only logins
     * @return Session[]
     */
    public static function byUser(User $user, ?bool $isApiKey = null): array {
        $result = model("z_login")->getSessionsByUserId($user, $isApiKey);

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

    /**
     * Names the session, or removes its name when null is passed
     */
    public function setName(?string $name): void {
        if($this->shouldRefresh) $this->refresh();

        model("z_login")->setSessionName($this, $name);

        $this->refreshOnNextUse();
    }

    /**
     * Marks the session as never expiring, or subjects it to the regular
     * lifetime again. A permanent session is not invalidated on use, so an
     * already expired session becomes usable again by turning this on.
     */
    public function setPermanent(bool $isPermanent): void {
        if($this->shouldRefresh) $this->refresh();

        model("z_login")->setSessionPermanent($this, $isPermanent);

        $this->refreshOnNextUse();
    }

    /**
     * Marks the session as an api key rather than an interactive login. The
     * flag only classifies the session, both kinds authenticate the same way.
     */
    public function setApiKey(bool $isApiKey): void {
        if($this->shouldRefresh) $this->refresh();

        model("z_login")->setSessionApiKey($this, $isApiKey);

        $this->refreshOnNextUse();
    }

    public function invalidate(): void {
        model("z_login")->invalidateSession($this);
        $this->nullId();
    }

    /**
     * The point in time the session becomes unusable, or null when it is permanent
     */
    public function expiresAt(bool $refresh = true): ?string {
        if($refresh && $this->shouldRefresh) $this->refresh();

        if($this->isPermanent()) return null;

        $lifetime = (int) config("loginTimeoutSeconds", TIMESPAN_DAY_7);

        if(!is_null($this->extendedSeconds())) $lifetime += $this->extendedSeconds();

        return date("Y-m-d H:i:s", strtotime($this->created()) + $lifetime);
    }

    public function isExpired(): bool {
        if($this->shouldRefresh) $this->refresh();

        $expiresAt = $this->expiresAt(false);

        if(is_null($expiresAt)) return false;

        return !(strtotime($expiresAt) > time());
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

    public function name(): ?string {
        return $this->getField("name");
    }

    public function isPermanent(): bool {
        return (bool) $this->getField("is_permanent");
    }

    public function isApiKey(): bool {
        return (bool) $this->getField("is_apikey");
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
