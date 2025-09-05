<?php

    class RequestModel extends z_model {

        public function getUsers() {
            $sql = "SELECT *
                    FROM `z_user`";
            return $this->exec($sql)->resultToArray();
        }
    } 

?>