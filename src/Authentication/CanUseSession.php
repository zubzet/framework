<?php
    namespace ZubZet\Framework\Authentication;

    use DateTime;
    use ZubZet\Framework\Authentication\Permission\User;


    trait CanUseSession {

        public static string $dbTable = "z_logintoken";

        protected bool $shouldRefresh = false;


        public function __construct(array $data) {
            parent::__construct($data);
            $this->loadObject($data);
        }

        public function loadObject(array $data) {
            $this->data = $data;
            $this->setField("userId", $data["userId"]);
            $this->setField("userId_exec", $data["userId_exec"]);
            $this->setField("name", $data["name"]);
            $this->setField("device", $data["device"]);
            $this->setField("reason", $data["reason"]);
            $this->setField("ip_creation", $data["ip_creation"]);
            $this->setField("ip_last", $data["ip_last"]);
            $this->setField("is_permanent", $data["is_permanent"]);
            $this->setField("is_apikey", $data["is_apikey"]);
            $this->setField("extended_seconds", $data["extended_seconds"]);
            $this->setField("expires_at", $data["expires_at"]);
            $this->setField("created", $data["created"]);
        }

        /**
         * A session by its token, whichever kind the token opens
         *
         * A token does not say what it opens, so the row picks the class. This is
         * the entry point of the authentication, which has to resolve both kinds -
         * an api key must not be measured against the login timeout.
         */
        public static function byToken(string $token): Session|APIKey|null {
            $result = model("z_login")->getSessionByToken($token);

            if(is_null($result)) return null;

            if($result["is_apikey"]) return new APIKey($result);

            return new Session($result);
        }

        /**
         * All active sessions of a user, of the kind the called class stands for
         *
         * @param User $user The user whose sessions are returned
         * @return static[]
         */
        public static function byUser(User $user): array {
            $result = model("z_login")->getSessionsByUserId($user, static::$dbExpression);

            if(empty($result)) return [];

            $sessions = [];
            foreach($result as $data) {
                $sessions[] = new static($data);
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
         * Fixes the point in time the session expires, or hands it back to the
         * computed lifetime when null is passed. A fixed expiry replaces that
         * lifetime, so extending the session no longer moves it.
         */
        public function setExpiresAt(?DateTime $expiresAt): void {
            if($this->shouldRefresh) $this->refresh();

            model("z_login")->setSessionExpiresAt($this, $expiresAt);

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
         * Subjects the session to expiring, or exempts it from it. A session that
         * cannot expire is not invalidated on use, so an already expired one
         * becomes usable again by turning this off.
         *
         * The column stores the exemption, so it is inverted here once.
         */
        public function setCanExpire(bool $canExpire): void {
            if($this->shouldRefresh) $this->refresh();

            model("z_login")->setSessionPermanent($this, !$canExpire);

            $this->refreshOnNextUse();
        }

        public function invalidate(): void {
            model("z_login")->invalidateSession($this);
            $this->nullId();
        }

        /**
         * The point in time the session becomes unusable, or null when it cannot expire
         *
         * A fixed expiry wins over the computed lifetime, so neither the configured
         * timeout nor an extension has a say once one is set. A session that cannot
         * expire outranks both - it never becomes unusable at all.
         */
        public function expiresAt(bool $refresh = true): ?string {
            if($refresh && $this->shouldRefresh) $this->refresh();

            if(!$this->canExpire()) return null;

            $fixedExpiry = $this->getField("expires_at");
            if(!is_null($fixedExpiry)) return $fixedExpiry;

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

        /**
         * The user agent the session was started from, or null when none was sent
         */
        public function device(): ?string {
            return $this->getField("device");
        }

        /**
         * Why the session was created, e.g. an impersonation, or null
         */
        public function reason(): ?string {
            return $this->getField("reason");
        }

        /**
         * The address the session was created from, or null when it was not known
         */
        public function ipCreation(): ?string {
            return $this->getField("ip_creation");
        }

        /**
         * The address the session was last used from, or null while it is unused
         */
        public function ipLast(): ?string {
            return $this->getField("ip_last");
        }

        /**
         * Whether the session is subject to expiring at all
         */
        public function canExpire(): bool {
            return !$this->getField("is_permanent");
        }

        public function extendedSeconds(): ?int {
            return $this->getField("extended_seconds");
        }

        public function created(): ?string {
            return $this->getField("created");
        }

        protected function refreshOnNextUse(): void {
            $this->shouldRefresh = true;
        }
    }
