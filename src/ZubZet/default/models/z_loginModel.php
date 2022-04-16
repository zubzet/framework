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
         * @return any[]|false The user data from the databaset or false if the token is wrong
         */
        function validateCookie($token) {
            $query = "SELECT * FROM `z_logintoken` WHERE `token`=?";
            $this->exec($query, "s", $token);
            return $this->getResult()->num_rows > 0 ? $this->getResult()->fetch_assoc() : false;
        }

        /**
         * Creates a login token for a user
         * @param int $userid Id of the user
         * @param int $exec_userId Id of the executing user
         * @return string The login token
         */
        function createLoginToken($userid, $exec_userId) {
            $token = str_replace('.', '', uniqid('', true)).rand(10000, 99999);
            $sql = "INSERT INTO `z_logintoken`(`userId`, `userId_exec`, `token`) VALUES (?, ?, ?)";
            $this->exec($sql, "iis", $userid, $exec_userId, $token);
            return $token;
        }

        /**
         * Gets an user by its email
         * @param string $email The email of the user to get
         * @return any[] The dataset of the user
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
            // todo: update this
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
         * @param any[]|false The dataset or false
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