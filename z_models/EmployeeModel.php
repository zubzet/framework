<?php

    class EmployeeModel extends z_model {
            
        function getMetaById($id) {
            $query = "SELECT `name`, `firstName`, `id`, `permissionLevel`, `tagId`, `languageId`, `email` FROM `employee` WHERE `id`=?";
            $this->exec($query, "i", $id);
            return $this->resultToLine();
        }

        function getMeta() {
            return $this->getFullTable("employee", "name, firstName, id");
        }

        function updateMetaById($name, $firstName, $email, $tagId, $permissionLEvel, $languageId, $id) {
            $query = "UPDATE `employee` SET `name`=?, `firstName`=?, `email`=?, `tagId`=?, `permissionLevel`=?, `languageId`=? WHERE `id` = ?";
            $this->exec($query, "sssiiii", $name, $firstName, $email, $tagId, $permissionLEvel, $languageId, $id);

            //Log
            $this->logAction($this->getLogCategoryIdByName("employee"), "Employee Meta updated (Employee ID: $id)");
        }

        /**
         * Adds an employee
         */
        function add($name, $firstName, $email, $tagId, $permissionLevel, $language) {
            $query = "INSERT INTO `employee`(`name`, `firstName`, `email`, `tagId`, `permissionLevel`, `languageId`) VALUES (?,?,?,?,?,?)";
            $this->exec($query, "sssiii", $name, $firstName, $email, $tagId, $permissionLevel, $language);
            $insertId = $this->getInsertId();

            //Log
            $this->logAction($this->getLogCategoryIdByName("employee"), "Employee $name.$firstName created");

            return $insertId;
        }

        function getCount() {
            return $this->countTableEntries("employee");
        }

        function getTagList() {
            return $this->getFullTable("employeetag");
        }

        /**
         * Updates the client email settings
         */
        function updateAccountSettings($id, $email, $notificationSkills, $notificationTime, $notificationPP, $language) {
            $query = "UPDATE `employee` SET `email`=?, `notificationsEnabled_Skills`=?, `notificationsEnabled_Time`=?, `notificationsEnabled_ProfilePicture`=?, `languageId`=? WHERE `id`=?";
            $this->exec($query, "siiiii", $email, $notificationSkills, $notificationTime, $notificationPP, $language, $id);

            //Log
            $this->logAction($this->getLogCategoryIdByName("employee"), "Employee account updated (Employee ID: $id)", $id);
        }

        function getPermissionLevelNames() {
            return $this->getFullTable("permissionlevelname");
        }

        function emailExistsExcludingEmployee($email, $employeeId) {
            $sql = "SELECT COUNT(*) AS CNT FROM `employee` WHERE `id` != ? AND UPPER(`email`) = UPPER(?)";
            $this->exec($sql, "is", $employeeId, $email);
            return $this->resultToLine()["CNT"] > 0;
        }

        function checkUniqueEmail($email) {
            $sql = "SELECT COUNT(*) AS CNT FROM `employee` WHERE UPPER(`email`) = UPPER(?)";
            $this->exec($sql, "s", $email);
            return $this->resultToLine()["CNT"] === 0;
        }

        function checkUniqueFirstNamelastName($firstName, $lastName) {
            $sql = "SELECT COUNT(*) AS CNT FROM `employee` WHERE 
                    (UPPER(`firstName`) = UPPER(?) AND UPPER(`name`) = UPPER(?)) OR
                    (UPPER(`firstName`) = UPPER(?) AND UPPER(`name`) = UPPER(?))";
            $this->exec($sql, "ssss", $firstName, $lastName, $lastName, $firstName);
            return $this->resultToLine()["CNT"] === 0;
        }

        function nameLastNamelExistsExcludingEmployee($firstName, $lastName, $id) {
            $sql = "SELECT COUNT(*) AS CNT FROM `employee` WHERE 
                    `id` != ? AND
                    ((UPPER(`firstName`) = UPPER(?) AND UPPER(`name`) = UPPER(?)) OR
                    (UPPER(`firstName`) = UPPER(?) AND UPPER(`name`) = UPPER(?)))";
            $this->exec($sql, "issss", $id, $firstName, $lastName, $lastName, $firstName);
            return $this->resultToLine()["CNT"] === 0;
        }

    }
?>