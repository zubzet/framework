<?php
    namespace ZubZet\Framework\Database\Migration\Commands\Traits;

    use ZubZet\Framework\Database\Endpoint;
    use ZubZet\Framework\Database\Migration\Type\TimeStamp;

    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Types\Type;
    use Doctrine\DBAL\DriverManager;
    use Doctrine\DBAL\Driver\Mysqli\Connection as MysqliDriverConnection;

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

            $endpoint = Endpoint::get();

            $parameters = [
                'dbname' => config("dbname"),
                'user' => $username,
                'password' => $password,
                'host' => $endpoint->host,
                'port' => $endpoint->port,
                'driver' => "mysqli",
            ];

            // Same transport as the runtime connection: a server enforcing
            // TLS rejects migrations outright otherwise.
            if($endpoint->tls) {
                [$ca, $caPath] = $endpoint->trustStore();

                $parameters['driverOptions'] = [
                    MysqliDriverConnection::OPTION_FLAGS => MYSQLI_CLIENT_SSL,
                ];

                if(!is_null($ca)) $parameters['ssl_ca'] = $ca;
                if(!is_null($caPath)) $parameters['ssl_capath'] = $caPath;
            }

            $connection = DriverManager::getConnection($parameters);

            // Used to map enum and vector types to string to avoid issues.
            // Vector is not properly supported, it is just treated as a string.
            // Method getDatabasePlatform does not exist below Doctrine DBAL 4.x
            if(method_exists($connection, 'getDatabasePlatform')) {
                $platform = $connection->getDatabasePlatform();
                foreach(["enum" => "string", "vector" => "string"] as $type => $mapping) {
                    if($platform->hasDoctrineTypeMappingFor($type)) continue;
                    $platform->registerDoctrineTypeMapping($type, $mapping);
                }
            }

            return $connection;
        }
    }
