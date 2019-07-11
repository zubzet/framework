<?php

    class z_statisticsModel extends z_model {

        function getLogCategories() {
            return $this->getFullTable("interaction_log_category");
        }

        function getLogTableByCategories($start, $end, array $categories) {
            $sql = "SELECT i.*
                    FROM `interaction_log` AS i
                    LEFT JOIN `user` AS e
                    ON i.`userId` = e.`id`
                    LEFT JOIN `user` AS e_exec
                    ON i.`userId_exec` = e_exec.`id`
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