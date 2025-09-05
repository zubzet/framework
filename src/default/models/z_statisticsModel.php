<?php
    /**
     * This file holds the statistics model
     */

    /**
     * The statistics model handles log data
     */
    class z_statisticsModel extends z_model {

        /**
         * Returns a list of all log categorys
         * @return array[] The list from the database
         */
        function getLogCategories() {
            return $this->getFullTable("z_interaction_log_category");
        }

        /**
         * Gets logs specified by attributes
         * @param string $start The start date from which logs should be returned in a sql format
         * @param string $end The end date to which the logs should be returned in sql format
         * @param int $categories A list of category ids
         * @return array[] The datasets from the database in an array
         */
        function getLogTableByCategories($start, $end, array $categories) {
            $sql = "SELECT i.*, e.email as `name`, e_exec.email as `name_exec`
                    FROM `z_interaction_log` i
                    LEFT JOIN `z_user` e
                    ON i.`userId` = e.`id`
                    LEFT JOIN `z_user` e_exec
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