<?php

    class z_fileModel extends z_model {

        function add($ref, $type, $name, $extension, $size) {
            $query = "INSERT INTO `file`(`reference`, `type`, `name`, `extension`, `size`) VALUES (?, ?, ?, ?, ?)";
            $this->exec($query, "ssssi", $ref, $type, $name, $extension, $size);
            return $this->getInsertId();
        }

    }

?>