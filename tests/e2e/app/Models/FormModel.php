<?php

    class FormModel extends z_model {

        public function getUploadedFiles() {
            $sql = "SELECT *
                    FROM `z_file`";
            return $this->exec($sql)->resultToArray();
        }

        public function getMediaFiles() {
            $sql = "SELECT * 
                    FROM `media`";

            return $this->exec($sql)->resultToArray();
        }

        public function uploadFile($file, $uploadDir, $zRoot) {
            if (empty($file) || !isset($file["name"]) || $file === null) {
                return false;
            }

            $ref = $this->getModel("z_general")->getUniqueRef();

            $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $target_file = $uploadDir . $ref . "." . $extension;

            if (move_uploaded_file($file["tmp_name"], $target_file) === false) {
                return false;
            }

            $this->getModel("z_file", $zRoot)->add(
                $ref, 
                $file["type"], 
                basename($file["name"]), 
                $extension, 
                $file["size"]
            );

            return true;
        }
    }

?>