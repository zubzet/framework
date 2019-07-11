<?php

    class z_loginModel extends z_model {

        //Validate a login token retrieved from the users Cookie
        function validateCookie($token) {
            $query = "SELECT * FROM `logintoken` WHERE `token`=?";
            $this->exec($query, "s", $token);
            return $this->getResult()->num_rows > 0 ? $this->getResult()->fetch_assoc() : false;
        }

        //Gets an user by an id
        function getUserById($userid) {
            $query = "SELECT * FROM `employee` WHERE `id`=?";
            $this->exec($query, "i", $userid);
            
            if ($this->getResult()->num_rows > 0) {
                return $this->getResult()->fetch_assoc(); 
            }
            return false;
        }

        /**
         * Creates a login token for a user
         */
        function createLoginToken($userid, $exec_userId) {
            $token = str_replace('.', '', uniqid('', true)).rand(10000, 99999);
            $sql = "INSERT INTO `logintoken`(`employeeId`, `employeeId_exec`, `token`) VALUES (?, ?, ?)";
            $this->exec($sql, "iis", $userid, $exec_userId, $token);
            return $token;
        }

        /**
         * Gets an user by the login (name.firstname)
         */
        function getUserByLogin($name) {
            $query = "SELECT * FROM `employee` WHERE CONCAT(UPPER(`Name`), '.', UPPER(`FirstName`))=UPPER(?)";
            $this->exec($query, "s", $name);
            if ($this->getResult()->num_rows < 1) return false;
            return $this->getResult()->fetch_assoc();
        }

        /** 
         * Updates the password of an user
         */
        function updatePassword($id, $pw) {
            $sql = "UPDATE `employee` SET `password`=?, `Salt`=? WHERE `id`=?";
            $this->exec($sql, "ssi", $pw["hash"], $pw["salt"], $id);
        }

        function findAccount($useremail) {
            $sql = "SELECT * FROM `employee` WHERE CONCAT(UPPER(`Name`), '.', UPPER(`FirstName`))=UPPER(?) || CONCAT(UPPER(`FirstName`), '.', UPPER(`Name`))=UPPER(?) || UPPER(`email`) = UPPER(?)";
            $this->exec($sql, "sss", $useremail, $useremail, $useremail);
            return $this->getResult()->fetch_assoc();
        }

        function newLoginTry($employeeId) {
            $sql = "INSERT INTO `logintry`(`employeeId`) VALUES (?)";
            $this->exec($sql, "i", $employeeId);
        }

        function countLoginTriesByTimeSpan($employeeId, $datetime) {
            $sql = "SELECT COUNT(*) AS CNT FROM `logintry` WHERE `employeeId` = ? AND `timestamp` >= ?";
            $this->exec($sql, "is", $employeeId, $datetime);
            return $this->resultToLine()["CNT"];
        }

        function addResetCode($employeeId, $ref, $reason) {
            $ref = strtoupper("SKDB-".base_convert(crc32($ref), 10, 36)."-".base_convert(crc32(time()), 10, 36));
            $sql = "INSERT INTO `password_reset`(`employeeId`, `refId`, `reason`, `active`) VALUES (?, ?, ?, 1)";
            $this->exec($sql, "iss", $employeeId, $ref, $reason);
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
        
        function addTooManyLoginsEmailBYEmployeeId($employeeId) {
            $sql = "INSERT INTO `login_too_many_tries`(`employeeId`) VALUES (?)";
            $this->exec($sql, "i", $employeeId);
        }

        function sendTooManyLoginsEmailBYEmployeeId($employeeId) {
            $sql = "SELECT COUNT(*) > 0 AS RES 
                    FROM `login_too_many_tries` 
                    WHERE `employeeId`=?
                    AND `created` >= ?";
            $timespan = date("Y-m-d H:i", strtotime("-10 minutes"));
            $this->exec($sql, "is", $employeeId, $timespan);
            return $this->resultToLine()["RES"] === 0;
        }
        

    }

?>