<?php

    class PersonalInformationModel extends z_model {
     
        function editByIdAndLanguageId($id, $languageId, $addr_country, $addr_state, $addr_city, $addr_zip, $addr_street, $addr_street_number, $email, $web, $tel, $mobil, $fax, $position, $nationality, $birthdate) {
            $query = "UPDATE `personalinformation` SET `addr_country`=?, `addr_state`=?, `addr_city`=?, `addr_zip`=?, `addr_street`=?, `addr_street_number`=?, `email`=?, `web`=?, `tel`=?, `mobil`=?, `fax`=?, `position`=?, `nationality`=?, `birthdate`=? WHERE `id`=? AND `languageId`=?";
            $this->exec($query, "ssssssssssssssii", $addr_country, $addr_state, $addr_city, $addr_zip, $addr_street, $addr_street_number, $email, $web, $tel, $mobil, $fax, $position, $nationality, $birthdate, $id, $languageId);

            //Log
            $this->logAction($this->getLogCategoryIdByName("personalinformation"), "Personal information updated (Information ID: $id, Language ID: $languageId)", $id);
        }

        function addByEmployeeIdAndLanguageId($languageId, $employeeId, $addr_country, $addr_state, $addr_city, $addr_zip, $addr_street, $addr_street_number, $email, $web, $tel, $mobil, $fax, $position, $nationality, $birthdate) {
            $query = "INSERT INTO `personalinformation`(`employeeId`, `languageid`, `addr_country`, `addr_state`, `addr_city`, `addr_zip`, `addr_street`, `addr_street_number`, `email`, `web`, `tel`, `mobil`, `fax`, `position`, `nationality`, `birthdate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->exec($query, "iissssssssssssss", $employeeId, $languageId, $addr_country, $addr_state, $addr_city, $addr_zip, $addr_street, $addr_street_number, $email, $web, $tel, $mobil, $fax, $position, $nationality, $birthdate);

            $this->logAction($this->getLogCategoryIdByName("personalinformation"), "Personal information added (Employee ID: $employeeId, Language ID: $languageId)");
        }

        function checkIfExistsByEmployeeIdAndLanguageId($employeeId, $languageId) {
            $query = "SELECT * FROM `personalinformation` WHERE `employeeId`=? AND `languageId`=?";
            $this->exec($query, "ii", $employeeId, $languageId);
            return $this->countResults() > 0;
        }

        function getByEmployeeIdAndLanguageId($id, $languageId) {
            $query = "SELECT * FROM `personalinformation` WHERE `active` = 1 AND `employeeId`=? AND `languageId`=?";
            $this->exec($query, "ii", $id, $languageId);
            return $this->resultToLine();
        }

    }

?>