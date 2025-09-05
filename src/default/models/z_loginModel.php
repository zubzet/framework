<?php
    /**
     * This file holds the login model
     */

    /**
     * The login model holds logging in and out of users
     */
    class z_loginModel extends z_model {

        /**
         * Validate a login token retrieved from the users Cookie
         * @param string $token The login token that is saved in the clients cookie
         * @return array|false The user data from the database or false if the token is wrong
         */
        public function validateCookie(string $token): bool|array {
            $sql = "SELECT *
                    FROM `z_logintoken`
                    WHERE `token` = ?
                    LIMIT 1";
            $session = $this->exec($sql, "s", $token)->resultToLine();
            if(is_null($session)) return false;

            // Get the session lifetime from the configuration
            $lifetime = (int) $this->z_db->booter->req->getBooterSettings(
                "loginTimeoutSeconds",
                TIMESPAN_DAY_7,
            );

            // Only return the session if it is not yet expired
            if((strtotime($session["created"]) + $lifetime) > time()) {
                return $session;
            }

            // Delete expired sessions that were tried anyways
            $this->booter->getModel("z_login")->invalidateSession(
                $session["token"],
            );
            return false;
        }

        /**
         * Invalidates and removes a login token (session) for a user
         * @param string $token The session identifier
         */
        public function invalidateSession(string $token): void {
            $sql = "DELETE FROM `z_logintoken` WHERE `token` = ?";
            $this->exec($sql, "s", $token);
        }

        /**
         * Creates a login token for a user
         * @param int $userId Id of the user
         * @param int $exec_userId Id of the executing user
         * @return string The login token
         */
        function createLoginToken(int $userId, int $exec_userId) {
            $token = bin2hex(random_bytes(20));
            $sql = "INSERT INTO `z_logintoken`(`userId`, `userId_exec`, `token`) VALUES (?, ?, ?)";
            $this->exec($sql, "iis", $userId, $exec_userId, $token);
            return $token;
        }

        /**
         * Gets an user by its email
         * @param string $email The email of the user to get
         * @return bool|array|null The dataset of the user
         */
        function getUserByLogin($email) {
            $query = "SELECT * FROM `z_user` WHERE email=?";
            $this->exec($query, "s", $email);
            if ($this->getResult()->num_rows < 1) return false;
            return $this->getResult()->fetch_assoc();
        }

        /** 
         * Updates the password of an user
         * @param int $id The id of the user
         * @param object $pw A password created by the password handler
         */
        function updatePassword($id, $pw) {
            $sql = "UPDATE `z_user` SET `password`=?, `Salt`=? WHERE `id`=?";
            $this->exec($sql, "ssi", $pw["hash"], $pw["salt"], $id);
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