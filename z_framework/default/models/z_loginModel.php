<?php

    class z_loginModel extends z_model {

        //Validate a login token retrieved from the users Cookie
        function validateCookie($token) {
            $query = "SELECT * FROM `logintoken` WHERE `token`=?";
            $this->exec($query, "s", $token);
            return $this->getResult()->num_rows > 0 ? $this->getResult()->fetch_assoc() : false;
        }

        /**
         * Creates a login token for a user
         */
        function createLoginToken($userid, $exec_userId) {
            $token = str_replace('.', '', uniqid('', true)).rand(10000, 99999);
            $sql = "INSERT INTO `logintoken`(`userId`, `userId_exec`, `token`) VALUES (?, ?, ?)";
            $this->exec($sql, "iis", $userid, $exec_userId, $token);
            return $token;
        }

        /**
         * Gets an user by the login (email)
         */
        function getUserByLogin($email) {
            $query = "SELECT * FROM `user` WHERE email=?";
            $this->exec($query, "s", $email);
            if ($this->getResult()->num_rows < 1) return false;
            return $this->getResult()->fetch_assoc();
        }

        /** 
         * Updates the password of an user
         */
        function updatePassword($id, $pw) {
            $sql = "UPDATE `user` SET `password`=?, `Salt`=? WHERE `id`=?";
            $this->exec($sql, "ssi", $pw["hash"], $pw["salt"], $id);
        }

        function newLoginTry($userId) {
            $sql = "INSERT INTO `logintry`(`userId`) VALUES (?)";
            $this->exec($sql, "i", $userId);
        }

        function countLoginTriesByTimeSpan($userId, $datetime) {
            $sql = "SELECT COUNT(*) AS CNT FROM `logintry` WHERE `userId` = ? AND `timestamp` >= ?";
            $this->exec($sql, "is", $userId, $datetime);
            return $this->resultToLine()["CNT"];
        }

        function addResetCode($userId, $ref, $reason) {
            $ref = strtoupper("SKDB-".base_convert(crc32($ref), 10, 36)."-".base_convert(crc32(time()), 10, 36));
            $sql = "INSERT INTO `password_reset`(`userId`, `refId`, `reason`, `active`) VALUES (?, ?, ?, 1)";
            $this->exec($sql, "iss", $userId, $ref, $reason);
            return $ref;
        }

        function getResetCode($code, $timespan) {
            $sql = "SELECT * FROM `password_reset` WHERE `refId` = ? AND `created` >= ? AND `active` = 1";
            $date = date('Y-m-d H:i:s', strtotime('-'.$timespan));
            $this->exec($sql, "ss", $code, $date);
            if ($this->getResult()->num_rows > 0) return $this->resultToLine(); 
            return false;
        }

        function disableResetCode($id) {
            $sql = "UPDATE `password_reset` SET `active` = 0 WHERE `id` = ?";
            $this->exec($sql, "i", $id);
        }
        
        function addTooManyLoginsEmailByUserId($userId) {
            $sql = "INSERT INTO `login_too_many_tries`(`userId`) VALUES (?)";
            $this->exec($sql, "i", $userId);
        }

        function sendTooManyLoginsEmailByUserId($userId) {
            $sql = "SELECT COUNT(*) > 0 AS RES 
                    FROM `login_too_many_tries` 
                    WHERE `userId`=?
                    AND `created` >= ?";
            $timespan = date("Y-m-d H:i", strtotime("-10 minutes"));
            $this->exec($sql, "is", $userId, $timespan);
            return $this->resultToLine()["RES"] === 0;
        }
        

    }

?>