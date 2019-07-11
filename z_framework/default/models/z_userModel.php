<?php

    class z_userModel extends z_model {
        
        //Gets an user by an id
        function getUserById($userid) {
            $query = "SELECT * FROM `user` WHERE `id`=?";
            $this->exec($query, "i", $userid);
            
            if ($this->getResult()->num_rows > 0) {
                return $this->getResult()->fetch_assoc(); 
            }
            return false;
        }

        function getUserList() {
            return $this->getFullTable("user");
        }

        /**
         * Adds an user
         */
        function add($email, $language) {
            $query = "INSERT INTO `user`(`email`, `languageId`) VALUES (?,?)";
            $this->exec($query, "si", $email, $language);
            $insertId = $this->getInsertId();

            //Log
            $this->logActionByCategory("user", "User $email created");

            return $insertId;
        }

        function getCount() {
            return $this->countTableEntries("user");
        }

        /**
         * Updates the client email settings
         */
        function updateAccountSettings($id, $email, $language) {
            $query = "UPDATE `user` SET `email`=?, `languageId`=? WHERE `id`=?";
            $this->exec($query, "siii", $email, $language, $id);

            //Log
            $this->logAction($this->getLogCategoryIdByName("user"), "User account updated (User ID: $id)", $id);
        }

        function getRoles($userId) {
            $sql = "SELECT * FROM user_role WHERE user = ? AND active = 1";
            $this->exec($sql, "i", $userId);
            return $this->resultToArray();
        }

        function createRole() {
            $sql = "INSERT INTO `role` () VALUES ()";
            $this->exec($sql);
            return $this->getInsertId();
        }

        function deactivateRole($roleId) {
            $sql = "UPDATE role SET active = 0 WHERE id = ?";
            $this->exec($sql, "i", $roleId);
        }

        function getPermissionsByUserId($userId) {
            $sql = "SELECT p.name FROM role_permission p LEFT JOIN user_role u ON p.role = u.role WHERE u.active = 1 ANd p.active = 1 AND u.user = ?";
            $this->exec($sql, "i", $userId);
            return $this->resultToArray();
        }

    }
?>