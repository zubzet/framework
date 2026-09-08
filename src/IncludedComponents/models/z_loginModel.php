<?php
    /**
     * This file holds the login model
     */

    use ZubZet\Framework\Authentication\APIKey;
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Database\IsInternalModel;
    use ZubZet\Framework\Authentication\PasswordHash\Password;
    use ZubZet\Framework\Authentication\Permission\User;

    /**
     * The login model holds logging in and out of users
     * @internal
     */
    class z_loginModel extends z_model {

        use IsInternalModel;

        /**
         * Retrieve a session by its token
         * @param string $token The token of the session to retrieve
         * @return array|null The dataset of the session or null if no session was found
         * @internal
         */
        public function getSessionByToken(string $token): ?array {
            $sql = "SELECT *
                    FROM `z_logintoken`
                    WHERE `token` = ?
                    AND `active` = 1
                    LIMIT 1";
            return $this->exec($sql, "s", $token)->resultToLine();
        }

        /**
         * Retrieve all active sessions of a user by its id
         * @param User $user The user object
         * @param array $dbExpression Narrows the result to one kind of session
         * @return array of session objects
         * @internal
         */
        public function getSessionsByUserId(User $user, array $dbExpression = []): array {
            $query = $this->dbSelect("*", [
                "zr" => "z_logintoken"
            ])->where([
                "zr.userId" => $user->id(),
                "zr.active" => 1,
            ]);

            if(!empty($dbExpression)) $query->where($dbExpression);

            return $this->exec($query)->resultToArray();
        }

        /**
         * Set the extension time for a specific logintoken
         * @param Session|APIKey $session The session to set the extension time for
         * @param int $seconds The seconds to extend the lifetime of the token
         * @internal
         */
        public function setExtensionTime(Session|APIKey $session, int $seconds): void {
            $sql = "UPDATE `z_logintoken`
                    SET `extended_seconds` = ?
                    WHERE `token` = ?
                    AND `active` = 1";
            $this->exec($sql, "is", $seconds, $session->token());
        }

        /**
         * Increase the extension time for a specific logintoken
         * @param Session|APIKey $session The session to increase the extension time for
         * @param int $seconds The seconds to increase the lifetime of the token
         * @internal
         */
        public function extendLoginToken(Session|APIKey $session, int $seconds): void {
            $sql = "UPDATE `z_logintoken`
                    SET `extended_seconds` = COALESCE(`extended_seconds`, 0) + ?
                    WHERE `token` = ?
                    AND `active` = 1";
            $this->exec($sql, "is", $seconds, $session->token());
        }

        /**
         * Names a session, or drops its name when null is passed
         * @param Session|APIKey $session The session to name
         * @param ?string $name The name of the session
         * @internal
         */
        public function setSessionName(Session|APIKey $session, ?string $name): void {
            $sql = "UPDATE `z_logintoken`
                    SET `name` = ?
                    WHERE `id` = ?";
            $this->exec($sql, "si", $name, $session->id());
        }

        /**
         * Exempts a session from the regular lifetime, or subjects it to it again
         * @param Session|APIKey $session The session to flag
         * @param bool $isPermanent Whether the session never expires
         * @internal
         */
        public function setSessionPermanent(Session|APIKey $session, bool $isPermanent): void {
            $sql = "UPDATE `z_logintoken`
                    SET `is_permanent` = ?
                    WHERE `id` = ?";
            $this->exec($sql, "ii", (int) $isPermanent, $session->id());
        }

        /**
         * Validate a Session object by checking if the token is not expired
         *
         * @param Session|APIKey $session The session to validate
         * @return bool True if the session is valid, false otherwise
         * @internal
         */
        public function validateSession(Session|APIKey $session) {
            if($session->isExpired()) {
                $session->invalidate();
                return false;
            }

            return true;
        }

        /**
         * Invalidates a login token (session) for a user by setting it to inactive in the database
         * @param Session|APIKey $session The session to invalidate
         * @internal
         */
        public function invalidateSession(Session|APIKey $session): void {
            $sql = "UPDATE `z_logintoken`
                    SET `active`= 0
                    WHERE `id` = ?";
            $this->exec($sql, "i", $session->id());
        }

        /**
         * Clears all sessions of a user by setting them to inactive in the database
         * @param User $user The user object
         * @internal
         */
        public function clearSessions(User $user): void {
            $sql = "UPDATE `z_logintoken`
                    SET `active`= 0
                    WHERE `userId` = ?";
            $this->exec($sql, "i", $user->id());
        }

        /**
         * Creates a login token for a user
         * @param int $userId Id of the user
         * @param int $exec_userId Id of the executing user
         * @param ?string $name An optional name for the session
         * @param bool $isApiKey Whether the token is an api key rather than a login
         * @return Session|APIKey The resulting session, an APIKey when flagged as one
         */
        function createLoginToken(int $userId, int $exec_userId, ?string $name = null, bool $isApiKey = false): Session|APIKey {
            $token = "zub-".bin2hex(random_bytes(32));
            $sql = "INSERT INTO `z_logintoken`(`userId`, `userId_exec`, `token`, `name`, `is_apikey`)
                    VALUES (?, ?, ?, ?, ?)";
            $this->exec($sql, "iissi", $userId, $exec_userId, $token, $name, (int) $isApiKey);
            return Session::byToken($token);
        }

        /**
         * Gets an user by its email
         * @param string $email The email of the user to get
         * @return bool|array|null The dataset of the user
         */
        function getUserByLogin($email) {
            $query = "SELECT * FROM `z_user` WHERE email=? AND email IS NOT NULL";
            $this->exec($query, "s", $email);
            if ($this->getResult()->num_rows < 1) return false;
            return $this->getResult()->fetch_assoc();
        }

        /**
         * Updates the password of an user
         * @param User $user The object of the user
         * @param string $password The raw user password
         * @internal Replaced by the User API (@see User::updatePassword()) for external use
         */
        public function updatePassword(User $user, string $password): void {
            $user->clearSessions();

            $sql = "UPDATE `z_user`
                    SET
                        `password` = ?,
                        `password_scheme` = ?,
                        `last_password_rehash_at` = CURRENT_TIMESTAMP(),
                        `salt` = NULL
                    WHERE `id` = ?";

            $this->exec(
                $sql, "ssi",
                Password::hash($password),
                Password::NATIVE,
                $user->id(),
            );
        }

        /**
         * Pure verification of a plaintext against a stored credential. Does NOT
         * upgrade the stored hash — use {@see User::verifyPassword()} for the login path.
         * @param string $password The plaintext password
         * @param string $hash The stored `password` value
         * @param ?string $salt The stored `salt` (legacy/onion rows need it; native ignores it)
         * @param ?string $scheme The stored `password_scheme`; inferred from the salt when null
         * @deprecated Use {@see \ZubZet\Framework\Authentication\PasswordHash\Password::verify()} instead.
         */
        public function checkPassword(string $password, string $hash, ?string $salt = null, ?string $scheme = null): bool {
            // Older callers pass (password, hash, salt) with no scheme; infer it from the salt.
            if(is_null($scheme)) {
                $scheme = empty($salt) ? Password::NATIVE : Password::LEGACY;
            }
            return Password::verify($password, $hash, $scheme, $salt)->isCorrect();
        }

        /**
         * Persist a freshly-upgraded native hash for a user, clearing the now
         * obsolete salt. This is the plain DB write behind the rehash-on-login /
         * onion-peel path; the verify-and-decide logic lives in
         * {@see User::verifyPassword()}.
         * @param User $user The user whose stored hash is being upgraded
         * @param string $hash The new native Argon2id hash to store
         */
        public function upgradeStoredHash(User $user, string $hash): void {
            $sql = "UPDATE `z_user`
                    SET
                        `password` = ?,
                        `password_scheme` = ?,
                        `last_password_rehash_at` = CURRENT_TIMESTAMP(),
                        `salt` = NULL
                    WHERE `id` = ?";
            $this->exec($sql, "ssi", $hash, Password::NATIVE, $user->id());
        }

        /**
         * Every `legacy` user row that still carries a hash to onion-wrap.
         * Passwordless rows (i.e. SSO / invite) also default to `legacy` but have nothing
         * to wrap, so they are excluded.
         *
         * @return array[] Rows of `id` and `password`
         */
        public function getLegacyPasswords(): array {
            $sql = "SELECT `id`, `password`
                    FROM `z_user`
                    WHERE `password_scheme` = ?
                    AND `password` IS NOT NULL
                    AND `password` <> ''";
            return $this->exec($sql, "s", Password::LEGACY)->resultToArray();
        }

        /**
         * Onion-wraps a user's stored legacy hash in place, marking the row `onion`
         * for at-rest protection of dormant accounts. The salt column is untouched.
         * @param int $userId The id of the user
         * @param string $legacyHash The stored legacy SHA-512 hash to wrap
         */
        public function onionWrapPassword(int $userId, string $legacyHash): void {
            $sql = "UPDATE `z_user`
                    SET `password` = ?, `password_scheme` = ?
                    WHERE `id` = ?";
            $this->exec(
                $sql,
                "ssi",
                Password::onionWrap($legacyHash),
                Password::ONION,
                $userId,
            );
        }

        /**
         * Rescords a new login try of a user
         * 
         * Counting to detect security issues
         * 
         * @param int $userId Id of the user
         */
        function newLoginTry($userId) {
            $sql = "INSERT INTO `z_logintry`(`userId`) VALUES (?)";
            $this->exec($sql, "i", $userId);
        }

        /**
         * Counts the login tries after a given time
         * @param int $userId Id of the user
         * @param string $datetime The datetime in SQL compatible format
         * @return int The number of login tries in this time
         */
        function countLoginTriesByTimeSpan($userId, $datetime) {
            $sql = "SELECT COUNT(*) AS CNT FROM `z_logintry` WHERE `userId` = ? AND `timestamp` >= ?";
            $this->exec($sql, "is", $userId, $datetime);
            return $this->resultToLine()["CNT"];
        }

        /**
         * Adds a code for the user to reset its password
         * @param int $userId The id of the user
         * @param string $ref The reference
         * @param string $reason The reason of the reset
         * @return string The reference
         */
        function addResetCode($userId, $ref, $reason) {
            $ref = strtoupper("ZIT-".base_convert(crc32($ref), 10, 36)."-".base_convert(crc32(time()), 10, 36));
            $sql = "INSERT INTO `z_password_reset`(`userId`, `refId`, `reason`, `active`) VALUES (?, ?, ?, 1)";
            $this->exec($sql, "iss", $userId, $ref, $reason);
            return $ref;
        }

        /**
         * Gets the dataset of a reset code
         * @param string $code The reset code
         * @param string $timespan A sql conform formatted time
         * @param false|array The dataset or false
         */
        function getResetCode($code, $timespan) {
            $sql = "SELECT * FROM `z_password_reset` WHERE `refId` = ? AND `created` >= ? AND `active` = 1";
            $date = date('Y-m-d H:i:s', strtotime('-'.$timespan));
            $this->exec($sql, "ss", $code, $date);
            if ($this->getResult()->num_rows > 0) return $this->resultToLine(); 
            return false;
        }

        /**
         * Disabled a password reset code
         * @param int $id Id of the reset code in the database
         */
        function disableResetCode($id) {
            $sql = "UPDATE `z_password_reset` SET `active` = 0 WHERE `id` = ?";
            $this->exec($sql, "i", $id);
        }
        
        /**
         * Adds a too many login try of a user in the databse
         * @param int $userId Id of the user
         */
        function addTooManyLoginsEmailByUserId($userId) {
            $sql = "INSERT INTO `z_login_too_many_tries`(`userId`) VALUES (?)";
            $this->exec($sql, "i", $userId);
        }

        /**
         * sendTooManyLoginsEmailByUserId
         * @param int $userId Id of the user
         * @return bool RES
         */
        function sendTooManyLoginsEmailByUserId($userId) {
            $sql = "SELECT COUNT(*) > 0 AS RES 
                    FROM `z_login_too_many_tries` 
                    WHERE `userId`=?
                    AND `created` >= ?";
            $timespan = date("Y-m-d H:i", strtotime("-10 minutes"));
            $this->exec($sql, "is", $userId, $timespan);
            return $this->resultToLine()["RES"] == 0;
        }
        

    }

?>