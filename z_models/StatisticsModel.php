<?php

    class StatisticsModel extends z_model {

        function getLogCategories() {
            return $this->getFullTable("interaction_log_category");
        }

        function getLogTableByCategories($start, $end, array $categories) {
            $sql = "SELECT i.*,
                    CONCAT(e_exec.`firstName`, ' ', e_exec.`name`) AS name_exec,
                    CONCAT(e.`firstName`, ' ', e.`name`) As name
                    FROM `interaction_log` AS i
                    LEFT JOIN `employee` AS e
                    ON i.`employeeId` = e.`id`
                    LEFT JOIN `employee` AS e_exec
                    ON i.`employeeId_exec` = e_exec.`id`
                    WHERE i.`created` >= ? 
                    AND i.`created` <= ? 
                    AND i.`categoryId` IN (";
            $elements = "";
            $types = "ss";
            foreach ($categories as $category) {
                $elements .= "?,";
                $types .= "i";
            }
            $sql .= rtrim($elements, ',') . ")";

            $this->exec($sql, $types, $start, $end, ...$categories);
            return $this->resultToArray();
        }

    }

?>