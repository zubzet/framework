<?php

    class DashboardModel extends z_model {

        function getLastTimeUpdateByEmployeeId($id) {
            $query = "SELECT `created` 
                            FROM `time` 
                            WHERE `employeeId`=?
                            AND `active` = 1
                            ORDER BY `created` DESC 
                            LIMIT 1";
            $this->exec($query, "i", $id);
            $created = $this->resultToLine()["created"];
            return $created == null ? '1-1-2000' : $created;
        }

        function getLastProfilePictureUpdateByEmployeeId($id) {
            $query = "SELECT f.`created`
                            FROM `employee` AS e
                            LEFT JOIN `file` AS f
                            ON e.`profile_picture` = f.`id`
                            WHERE e.`id` = ?
                            AND `profile_picture` != 1
                            ORDER BY f.`created`
                            LIMIT 1";
            $this->exec($query, "i", $id);
            $created = $this->resultToLine()["created"];
            return $created == null ? '2000-1-1' : $created;
        }

        function getLoginCount() {
            return $this->countTableEntries("logintoken");
        }

        function getDashboardEmployeeTable() {
            $query = "SELECT e.`name` AS name, e.`firstName` AS firstname, e.`email` AS email, e.`created` AS created, e.`password` AS pw, p.`name` AS permission, t.`name` AS tag
                            FROM `employee` AS e
                            LEFT JOIN `permissionlevelname` AS p
                            ON e.`permissionLevel` = p.`id`
                            LEFT JOIN `employeetag` AS t
                            ON e.`tagId` = t.`id`";
            $this->exec($query);
            return $this->resultToArray();
        }

        function getTutorialStatusByEmployeeId($employeeId) {
            $sql = "SELECT `hasFinishedTheTutorial` AS hft FROM `employee` WHERE `id`=?";
            $this->exec($sql, "i", $employeeId);
            return $this->resultToLine()["hft"] == 1;
        }

        function finishTutorialByEmployeeId($employeeId) {
            $sql = "UPDATE `employee` SET `hasFinishedTheTutorial`=1 WHERE `id`=?";
            $this->exec($sql, "i", $employeeId);
        }

        function reactivateTutorialByEmployeeId($employeeId) {
            $sql = "UPDATE `employee` SET `hasFinishedTheTutorial`=0 WHERE `id`=?";
            $this->exec($sql, "i", $employeeId);
        }

    }

?>