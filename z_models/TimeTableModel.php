<?php

    class TimeTableModel extends z_model {

        function getTimeTableByEmployeeId($id) {
            $query = "SELECT * FROM `time` WHERE `active`=1 AND `employeeId`=$id ORDER BY `day` ASC, `start` ASC, `created` ASC";
            $this->exec($query);
            return $this->resultToArray();
        }

        function deleteAllTimeTableLinesByEmployeeId($id) {
            $query = "UPDATE `time` SET `active`=0 WHERE `employeeId`=$id";

            $this->exec($query);

            $this->logAction($this->getLogCategoryIdByName("timetable"), "Time table rows deleted (Employee ID: $id)", $id);
        }
        
        function addTimeRowByEmployeeId($employeeId, $day, $start, $end, $duration) {
            $query = "INSERT INTO `time`(`employeeId`, `day`, `start`, `end`, `duration`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($query, "isssi", $employeeId, $day, $start, $end, $duration);

            $this->logAction($this->getLogCategoryIdByName("timetable"), "Time table row added (Employee ID: $employeeId)", $employeeId);
        }

        function getTimeTableRowsByEmployyeeId($start, $end, array $employees) {
            $types = "ss";
            $sql = "SELECT * FROM `time` WHERE `active`=1 AND `day` >= ? AND `day` <= ? AND `employeeId` IN (";
            $elements = "";
            foreach ($employees as $employee) {
                $elements .= "?,";
                $types .= "i";
            }
            $sql .= rtrim($elements, ",") . ")";
            $this->exec($sql, $types, $start, $end, ...$employees);
            return $this->resultToArray();
        }

        function getLastUpdates() {
            $query = "SELECT e.`id`, e.`email`, MAX(a.`created`) AS last_update, e.`notificationsEnabled_Time` AS NE
                      FROM `employee` AS e 
                      LEFT JOIN `time` AS a
                      ON e.`id` = a.`employeeId` 
                      AND a.`active` = 1
                      GROUP BY e.`id`";
            $this->exec($query);
            return $this->resultToArray();
        }

    }

?>