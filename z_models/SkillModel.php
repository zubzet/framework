<?php
    class SkillModel extends z_model {

        function getOccurrences() {
            $query = "SELECT ifnull(OCC.VAL, 0) AS OCC, SK.name, SK.id, SK.categoryId
                            FROM `skill` AS SK
                            LEFT JOIN (
                                SELECT *, COUNT(*) AS VAL
                                FROM `skillassignment`
                                WHERE `active` = 1
                                GROUP BY `skillId`
                            ) AS OCC
                            ON SK.`id` = OCC.`skillId`
                            WHERE SK.active = 1";
            $this->exec($query);
            return $this->resultToArray();
        }

        /**
         * Removes a skill from the pool
         */
        function deleteById($id) {
            $query = "UPDATE `skill` SET `active`=0 WHERE `id`=?";
            $this->exec($query, "i", $id);
            $query = "UPDATE `skillassignment` SET `active`=0 WHERE `skillId`=?";
            $this->exec($query, "i", $id);

            $this->logAction($this->getLogCategoryIdByName("skill"), "Skill deleted (Skill ID: $id)", $id);
        }

        /**
         * Adds a skill to the skill pool
         */
        function add($name, $categoryId) {
            $query = "INSERT INTO `skill`(`name`, `categoryId`) VALUES (?, ?)";
            $this->exec($query, "ss", $name, $categoryId);

            $this->logAction($this->getLogCategoryIdByName("skill"), "Skill added (Name: $name, Category ID: $categoryId)", $name);
        }

        /**
         * Edits the details of a skill
         */
        function editById($id, $name, $categoryId) {
            $query = "UPDATE `skill` SET `name`=?, `categoryId`=? WHERE `id`=?";
            $this->exec($query, "ssi", $name, $categoryId, $id);

            $this->logAction($this->getLogCategoryIdByName("skill"), "Skill edited (Skill ID: $id)", $id);
        }

        function getAssignmentsByEmployeeId($id) {
            $query = "SELECT SKA.`id` AS skillAssignmentId, SKA.`experience`, SK.`id` as skillId, SKS.`id` as skillScaleId, SKS.`name` as skillScaleName
                            FROM `skillassignment` AS SKA 
                            LEFT JOIN `skill` AS SK ON SK.`id` = SKA.`skillId`
                            LEFT JOIN `skillscale` AS SKS ON SKS.`id` = SKA.`scaleId`
                            WHERE SKA.`active` = 1
                            AND SKA.`employeeId` = ?";
            $this->exec($query, "i", $id);
            return $this->resultToArray();
        }

        function deleteAssignmentById($id) {
            $query = "UPDATE `skillassignment` SET `active`=0 WHERE `id`=?";
            $this->exec($query, "i", $id);

            $this->logAction($this->getLogCategoryIdByName("skill"), "Skill assigment deleted (Assignment ID: $id)", $id);
        }

        function editAssignmentById($id, $scaleId, $experience) {
            $query = "UPDATE `skillassignment` SET `scaleId`=?, `experience`=? WHERE `id`=?";
            $this->exec($query, "idi", $scaleId, $experience, $id);

            $this->logAction($this->getLogCategoryIdByName("skill"), "Skill assigment edited (Assignment ID: $id)", $id);
        }

        function addAssignment($skillId, $scaleId, $experience, $employeeId) {
            $query = "INSERT INTO `skillassignment`(`skillId`, `scaleId`, `experience`, `employeeId`) VALUES (?, ?, ?, ?)";
            $this->exec($query, "iidi", $skillId, $scaleId, $experience, $employeeId);

            $this->logAction($this->getLogCategoryIdByName("skill"), "Skill assigment added (Skill ID: $skillId)", $skillId);
        }

        function getLastUpdateByEmployeeId($id) {
            $query = "SELECT `created` 
                            FROM `skillassignment` 
                            WHERE `employeeId`=?
                            AND `active` = 1
                            ORDER BY `created` DESC 
                            LIMIT 1";
            $this->exec($query, "i", $id);
            $created = $this->resultToLine()["created"];
            return $created === NULL ? "1-1-2000" : $created;
        }

        function getLastUpdates() {
            $query = "SELECT e.`id`, e.`email`, MAX(a.`created`) AS last_update, e.`notificationsEnabled_Skills` AS NE
                      FROM `employee` AS e 
                      LEFT JOIN `skillassignment` AS a
                      ON e.`id` = a.`employeeId` 
                      AND a.`active` = 1
                      GROUP BY e.`id`";
            $this->exec($query);
            return $this->resultToArray();
        }

        function getCategories() {
            return $this->getFullTable("skillcategory");
        }

        function getScales() {
            return $this->getFullTable("skillscale");
        }

    }
?>