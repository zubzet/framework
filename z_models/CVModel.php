<?php

    class CVModel extends z_model {

        function getCvGenerateCount() {
            return $this->countTableEntries("cvgenerate");
        }

        function addGeneration($employeeId) {
            $query = "INSERT INTO `cvgenerate`(`employeeId`) VALUES (?)";
            $this->exec($query, "i", $employeeId);

            //Log
            $this->logAction($this->getLogCategoryIdByName("cv"), "Generated CV", $employeeId);
        }

        function updateProfilePicture($employeeId, $fileId) {
            $query = "UPDATE `employee` SET `profile_picture`=? WHERE `id`=?";
            $this->exec($query, "ii", $fileId, $employeeId);

            //Log
            $this->logAction($this->getLogCategoryIdByName("cv"), "Profile picture updated", $employeeId);
        }

        function getProfilePictureByEmployeeId($employeeId) {
            $query = "SELECT F.*
                      FROM `employee` AS E
                      LEFT JOIN `file` AS F
                      ON F.`id` = E.profile_picture
                      WHERE E.`id` = ?";
            $this->exec($query, "i", $employeeId);
            return $this->resultToArray();
        }

        function getLastProfilePictureUpdates() {
            $query = "SELECT e.`id`, e.`email`, MAX(f.`created`) AS last_update, e.`notificationsEnabled_ProfilePicture` AS NE
                      FROM `employee` AS e 
                      LEFT JOIN `file` AS f
                      ON e.`profile_picture` = f.`id` 
                      AND e.`profile_picture` != 1
                      GROUP BY e.`id`";
            $this->exec($query);
            return $this->resultToArray();
        }

        function getPublishedRefsByEmployeeId($employeeId) {
            $sql = "SELECT * FROM `cv_published_link` WHERE `employeeId`=? AND `active`=1 ORDER BY `created` DESC";
            $this->exec($sql, "i", $employeeId);
            return $this->resultToArray();
        }

        function addPublishedRefsByEmployeeId($employeeId, $comment, $languageId, $ref) {
            $sql = "INSERT INTO `cv_published_link`(`employeeId`, `comment`, `languageId`, `ref`) VALUES (?, ?, ?, ?)";
            $this->exec($sql, "isis", $employeeId, $comment, $languageId, $ref);
        }

        function removePublishedRefsId($id, $employeeId) {
            $sql = "UPDATE `cv_published_link` SET `active`=0 WHERE `id`=? AND `employeeId`=?";
            $this->exec($sql, "ii", $id, $employeeId);
        }

        function getDetailsByRef($ref) {
            $sql = "SELECT * FROM `cv_published_link` WHERE `ref`=?";
            $this->exec($sql, "s", $ref);
            return $this->resultToLine();
        }

        function addLinkViewsById($id) {
            $sql = "UPDATE `cv_published_link` SET `views`=(`views`+1) WHERE `id`=?";
            $this->exec($sql, "i", $id);
        }

    }

?>