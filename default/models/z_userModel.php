<?php
    /**
     * File that defines the user model
     */

    /**
     * User Model
     * 
     * This model handles database stuff with the focus on user managment.
     * An instance of this class can be acquired with z_framework::getModel("z_user")
     */
    class z_userModel extends z_model {
        
        /**
         * Returns a user row of the database, selected by the users id
         * @param int $userid ID of the user we want the data about
         * @return bool|array|null The dataset
         */
        function getUserById($userid) {
            $query = "SELECT * FROM `z_user` WHERE `id`=?";
            $this->exec($query, "i", $userid);
            
            if ($this->getResult()->num_rows > 0) {
                return $this->getResult()->fetch_assoc(); 
            }
            return false;
        }

        /**
         * Returns a user row of the database, selected by the users email address
         * @param string $email Email of the user from who we want the data about
         * @return bool|array|null The dataset
         */
        function getUserByEmail($email) {
            $query = "SELECT * FROM `z_user` WHERE `email`=?";
            $this->exec($query, "s", $email);
            
            if ($this->getResult()->num_rows > 0) {
                return $this->getResult()->fetch_assoc(); 
            }
            return false;
        }

        /**
         * Returns all user data from the database
         * @return array[] The table as a two dimensional array
         */
        function getUserList() {
            return $this->getFullTable("z_user");
        }

        /**
         * Creates an user account
         * @param string $email Email of the user
         * @param int $language Id of users language
         * @param string $passwordString The raw users password. Not hashed! It will be hashed in this function
         * @return int The id of the new created user
         */
        function add($email, $language, $passwordString = null, $verified = null) {
            if($verified === null) {
                $query = "INSERT INTO `z_user`(`email`, `languageId`) VALUES (?, ?)";
                $this->exec($query, "ss", $email, $language);
            } else {
                $query = "INSERT INTO `z_user`(`email`, `languageId`, `verified`) VALUES (?, ?, ?)";
                $this->exec($query, "sss", $email, $language, $verified);
            }
            $insertId = $this->getInsertId();

            //Log
            $this->logActionByCategory("user", "User $email created");

            if ($passwordString !== null) {
                $password = passwordHandler::createPassword($passwordString);
                $this->updatePassword($insertId, $password);
            }

            return $insertId;
        }

        /**
         * Sets the password for a user
         * @param int $id Id of the user which password should be changed
         * @param string $pw The raw unhashed new password
         */
        function updatePassword($id, $pw) {
            $sql = "UPDATE `z_user` SET `password`=?, `Salt`=? WHERE `id`=?";
            $this->exec($sql, "ssi", $pw["hash"], $pw["salt"], $id);
        }

        /**
         * Gets the number of registered users
         * @return int The number of registered users
         */
        function getCount() {
            return $this->countTableEntries("z_user");
        }

        /**
         * Updates the clients settings
         * @param int $id Id of the target user
         * @param string $email The new email
         * @param int $language The new language id
         */
        function updateAccountSettings($id, $email, $language) {
            $query = "UPDATE `z_user` SET `email`=?, `languageId`=? WHERE `id`=?";
            $this->exec($query, "siii", $email, $language, $id);

            //Log
            $this->logAction($this->getLogCategoryIdByName("user"), "User account updated (User ID: $id)", $id);
        }

        /**
         * Gets all the roles a user has
         * @param int $userId The id of the target user
         * @return array[] The datasets of the user_role table
         */
        function getRoles($userId) {
            $sql = "SELECT * FROM z_user_role WHERE user = ? AND active = 1";
            $this->exec($sql, "i", $userId);
            return $this->resultToArray();
        }

        /**
         * Creates a role
         * @return int The id of the new created role
         */
        function createRole() {
            $sql = "INSERT INTO `z_role` () VALUES ()";
            $this->exec($sql);
            return $this->getInsertId();
        }

        /**
         * Deactivates a role
         * 
         * After a role is deactivated the users with it will loose the role specific permissions as long as they don't have another role with these.
         * 
         * @param int $roleId The id of the role to deactivate
         */
        function deactivateRole($roleId) {
            $sql = "UPDATE z_role SET active = 0 WHERE id = ?";
            $this->exec($sql, "i", $roleId);
        }

        /**
         * Add a role to a user
         * @param int $userId The id of the user itended to recieve the role
         * @param int $roleId The id of the role to be added
         */
        function addRoleToUserByRoleId($userId, $roleId) {
            $sql = "INSERT INTO `z_user_role`(`role`, `user`) VALUES (?, ?)";
            $this->exec($sql, "ii", $roleId, $userId);
        }

        /**
         * Change the state of a role granted to a user, Can be used to add or remove roles
         * @param int $userId The id of the user
         * @param int $roleId The id of the role
         */
        function changeRoleStateByUserIdAndRoleId($userId, $roleId, $shouldHaveRole = true) {
            // If the user should not have the role, invalidate all grants of it
            if(!$shouldHaveRole) {
                $sql = "UPDATE `z_user_role` 
                        SET `active` = 0 
                        WHERE `user` = ? 
                        AND `role` = ?
                        AND `active` = 1";
                return $this->exec($sql, "ii", $userId, $roleId);
            }

            // Find out if the user already has the role
            $sql = "SELECT COUNT(*) > 0 AS has_role 
                    FROM `z_user_role` 
                    WHERE `role` = ? 
                    AND `user` = ? 
                    AND `active` = 1";
            $hasRole = $this->exec($sql, "ii", $roleId, $userId)->resultToLine()["has_role"];

            // If the user does not have the role, grant it
            if(!$hasRole) {
                $sql = "INSERT INTO `z_user_role`(`role`, `user`) VALUES (?, ?)";
                $this->exec($sql, "ii", $roleId, $userId);
            }
        }

        /**
         * Gets all permissions a specific user has
         * 
         * @param int $userId Id of the target user
         * @return string[] Array filled with permissions
         */
        function getPermissionsByUserId($userId) {
            $sql = "SELECT p.name FROM z_role_permission p LEFT JOIN z_user_role u ON p.role = u.role WHERE u.active = 1 ANd p.active = 1 AND u.user = ?";
            $this->exec($sql, "i", $userId);
            $arr = $this->resultToArray();
            $out = [];
            foreach ($arr as $perm) {
                $out[] = $perm["name"];
            }
            return $out;
        }

        /**
         * Verifies an users mail address
         * @param string $token. The token from the url the user clicked on.
         */
        function verifyUser($token) {
            if (!$token) return false;

            // Retrieve the token from the database
            $sql = "SELECT zev.*
                    FROM `z_email_verify` AS zev
                    LEFT JOIN `z_user` AS zu
                    ON zev.`user` = zu.`id`
                    WHERE `token` = ?
                    AND (
                        -- Either the token is still valid and unused
                        zev.`active` = 1
                        -- Or the user is already verified
                        OR zu.`verified` IS NOT NULL
                    )
                    LIMIT 1";
            $this->exec($sql, "s", $token);
            $result = $this->resultToLine();

            // If the token was not found, the attempt is invalid
            if (!isset($result)) return false;

            // Verification is invalid if the token is too old
            if (time() > strtotime($result["end"])) return false;

            // Remove the token from the database
            $sql = "UPDATE z_email_verify
                    SET active = 0
                    WHERE id = ?";
            $this->exec($sql, "i", $result["id"]);

            // Mark the user as verified
            $sql = "UPDATE z_user
                    SET verified = CURRENT_TIMESTAMP()
                    WHERE id = ?";
            $this->exec($sql, "i", $result["user"]);

            return true;
        }

        /**
         * Creates an email verify token and puts it into the database
         * @param int $userId;
         */
        function createVerifyToken($userId) {
            $token = uniqid("v_");
            $endDate = date('Y-m-d H:i:s', strtotime('+1 day'));
            $sql = "INSERT INTO `z_email_verify`(token, user, end) VALUES (?, ?, ?)";
            $this->exec($sql, "sis", $token, $userId, $endDate);
            return $token;
        }

        /**
         * Gets the id of a role by it's name
         * @param string $name The name of the role
         * @return int The id of the role
         */
        public function getRoleIdByRoleName(string $name): ?int {
            $sql = "SELECT `id`
                    FROM `z_role`
                    WHERE `name` = ?
                    LIMIT 1";
            $this->exec($sql, "s", $name);
            return $this->resultToLine()["id"] ?? null;
        }

    }
?>