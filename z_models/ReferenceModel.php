<?php

    class ReferenceModel extends z_model {

        function getByEmployeeIdAndLanguageId($id, $languageId) {
            $query = "SELECT * FROM `cvreferences` WHERE `employeeId`=? AND `languageId`=? AND `active` = 1 ORDER BY `start` DESC, `end` DESC";
            $this->exec($query, "ii", $id, $languageId);
            return $this->resultToArray();
        }

        function deleteById($id) {
            $query = "UPDATE `cvreferences` SET `active`=0 WHERE `id`=?";
            $this->exec($query, "i", $id);

            $this->logAction($this->getLogCategoryIdByName("references"), "References item deleted (Reference ID: $id)", $id);
        }

        function editById($employeeId, $title, $description, $short_description, $position, $client, $start, $end, $skillId, $id) {
            $query = "UPDATE `cvreferences` SET `employeeId`=?, `title`=?, `description`=?, `short_description`=?, `position`=?, `client`=?, `start`=?, `end`=?, `skillId`=? WHERE `id`=?";
            $this->exec($query, "isssssssii", $employeeId, $title, $description, $short_description, $position, $client, $start, $end, $skillId, $id);

            $this->logAction($this->getLogCategoryIdByName("references"), "References item updated (Reference ID: $id)", $id);
        }

        function add($employeeId, $languageId, $title, $description, $short_description, $position, $client, $start, $end, $skillId) {
            $query = "INSERT INTO `cvreferences`(`languageId`, `employeeId`, `title`, `description`, `short_description`, `position`, `client`, `start`, `end`, `skillId`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->exec($query, "iisssssssi", $languageId, $employeeId, $title, $description, $short_description, $position, $client, $start, $end, $skillId);

            $this->logAction($this->getLogCategoryIdByName("references"), "References item added (EmployeeID: $employeeId)", $employeeId);
        }
        
    }
?>