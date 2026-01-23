<?php
    namespace ZubZet\Framework\Migration\Commands\Traits;

    use Doctrine\DBAL\DriverManager;
    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Types\Type;
    use ZubZet\Framework\Migration\Type\TimeStamp;

    trait DbalConnection {
        private function createDbalConnection(): Connection {
            Type::hasType("timestamp") || Type::addType("timestamp", TimeStamp::class);

            $usernameElevated = config('dbusername_elevated');
            $passwordElevated = config('dbpassword_elevated');

            // Use default credentials if elevated ones are not set
            if(empty($usernameElevated) || empty($passwordElevated)) {
                $username = config("dbusername");
                $password = config("dbpassword");
            }

            return DriverManager::getConnection([
                'dbname' => config("dbname"),
                'user' => $username,
                'password' => $password,
                'host' => config("dbhost"),
                'port' => config("dbport"),
                'driver' => "mysqli",
            ]);
        }
    }
