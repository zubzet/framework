<?php
    /**
     * This file holds the file model
     */

    /**
     * The file model handles dynamic files
     */
    class z_fileModel extends z_model {

        /**
         * Adds a reference to a file to the database
         * @param string $ref The reference
         * @param string $type Type
         * @param string $name Name of the file
         * @param string $extension Extension of the file
         * @param int $size Size of the file
         * @return int The id of the created dataset
         */
        function add($ref, $type, $name, $extension, $size) {
            $query = "INSERT INTO `z_file`(`reference`, `type`, `name`, `extension`, `size`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($query, "ssssi", $ref, $type, $name, $extension, $size);
            return $this->getInsertId();
        }

        /**
         * Get a file by it's fileId
         * @param int $fileId The file id
         * @return string[] The id of the created dataset
         */
        function getById($fileId) {
            $sql = "SELECT * FROM `z_file` WHERE `id`=? LIMIT 1";
            $this->exec($sql, "i", $fileId);
            return $this->resultToLine();
        }

    }

?>