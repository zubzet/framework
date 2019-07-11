<?php

    class ProfessionalHistoryModel extends z_model {
     
        function deleteById($personalInformationId, $id) {
            $query = "UPDATE `professionalhistory` SET `active`=0 WHERE id=? AND personalInformationId=?";
            $this->exec($query, "ii", $id, $personalInformationId);

            $this->logAction($this->getLogCategoryIdByName("professionalhistory"), "Professional history item removed (Personal information ID: $personalInformationId, Item ID: $id)", $id);
        }

        function editById($personalInformationId, $id, $start, $end, $title, $position) {
            $query = "UPDATE `professionalhistory` SET `start`=?,`end`=?,`title`=?,`position`=? WHERE id=? AND personalInformationId=?";
            $this->exec($query, "ssssii", $start, $end, $title, $position, $id, $personalInformationId);

            $this->logAction($this->getLogCategoryIdByName("professionalhistory"), "Professional history item updated (Personal information ID: $personalInformationId, Item ID: $id)", $id);
        }

        function add($personalInformationId, $start, $end, $title, $position) {
            $query = "INSERT INTO `professionalhistory`(`personalInformationId`, `start`, `end`, `title`, `position`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($query, "issss", $personalInformationId, $start, $end, $title, $position);

            $this->logAction($this->getLogCategoryIdByName("professionalhistory"), "Professional history item added (Personal information ID: $personalInformationId)", $personalInformationId);
        }

        function getByPersonalInformationId($id) {
            $query = "SELECT * FROM `professionalhistory` WHERE `personalInformationId`=? AND `active` = 1 ORDER BY `start` ASC, `end` ASC";
            $this->exec($query, "i", $id);
            return $this->resultToArray();
        }

    }

?>