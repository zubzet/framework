<?php
    namespace ZubZet\Framework\Migration\Commands\Traits;

    trait DatabaseConnection {
        private function setDatabaseConnection() {
            $username = config("dbusername_elevated");
            $password = config("dbpassword_elevated");

            if(!empty($username) && !empty($password)) {
                db()->switchUser($username, $password);
            }
        }
    }
