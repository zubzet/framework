<?php

    class z_userModel extends z_model {
        
        //Gets an user by an id
        function getUserById($userid) {
            $query = "SELECT * FROM `z_user` WHERE `id`=?";
            $this->exec($query, "i", $userid);
            
            if ($this->getResult()->num_rows > 0) {
                return $this->getResult()->fetch_assoc(); 
            }
            return false;
        }

        function getUserList() {
            return $this->getFullTable("z_user");
        }

        /**
         * Adds an user
         */
        function add($email, $language, $passwordString = null) {
            $query = "INSERT INTO `z_user`(`email`, `languageId`) VALUES (?,?)";
            $this->exec($query, "ss", $email, $language);
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
         */
        function updatePassword($id, $pw) {
            $sql = "UPDATE `z_user` SET `password`=?, `Salt`=? WHERE `id`=?";
            $this->exec($sql, "ssi", $pw["hash"], $pw["salt"], $id);
        }

        /**
         * Gets the number of registered users
         */
        function getCount() {
            return $this->countTableEntries("z_user");
        }

        /**
         * Updates the client email settings
         */
        function updateAccountSettings($id, $email, $language) {
            $query = "UPDATE `z_user` SET `email`=?, `languageId`=? WHERE `id`=?";
            $this->exec($query, "siii", $email, $language, $id);

            //Log
            $this->logAction($this->getLogCategoryIdByName("user"), "User account updated (User ID: $id)", $id);
        }

        function getRoles($userId) {
            $sql = "SELECT * FROM z_user_role WHERE user = ? AND active = 1";
            $this->exec($sql, "i", $userId);
            return $this->resultToArray();
        }

        function createRole() {
            $sql = "INSERT INTO `z_role` () VALUES ()";
            $this->exec($sql);
            return $this->getInsertId();
        }

        function deactivateRole($roleId) {
            $sql = "UPDATE z_role SET active = 0 WHERE id = ?";
            $this->exec($sql, "i", $roleId);
        }

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

    }
?>