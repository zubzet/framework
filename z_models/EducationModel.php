<?php

    class EducationModel extends z_model {
     
        function deleteById($personalInformationId, $id) {
            $query = "UPDATE `education` SET `active`=0 WHERE `id`=? AND `personalInformationId`=?";
            $this->exec($query, "ii", $id, $personalInformationId);

            //Log
            $this->logAction($this->getLogCategoryIdByName("cv"), "Education item deleted", $personalInformationId);
        }

        function editById($personalInformationId, $id, $start, $graduation, $title, $description) {
            $query = "UPDATE `education` SET `start`=?, `graduation`=?, `title`=?, `description`=? WHERE id=? AND personalInformationId=?";
            $this->exec($query, "ssssii", $start, $graduation, $title, $description, $id, $personalInformationId);

            //Log
            $this->logAction($this->getLogCategoryIdByName("cv"), "Education item updated", $personalInformationId);
        }

        function add($personalInformationId, $start, $graduation, $title, $description) {
            $query = "INSERT INTO `education`(`personalInformationId`, `start`, `graduation`, `title`, `description`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($query, "issss", $personalInformationId, $start, $graduation, $title, $description);

            //Log
            $this->logAction($this->getLogCategoryIdByName("cv"), "Education item added", $personalInformationId);
        }

        function getByPersonalInformationId($id) {
            $query = "SELECT * FROM `education` WHERE `personalInformationId`=? AND `active` = 1 ORDER BY `graduation` DESC, `start` DESC";
            $this->exec($query, "i", $id);
            return $this->resultToArray();
        }

    }

?>