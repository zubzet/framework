<?php
    namespace ZubZet\Framework\Migration\Commands\Traits;

    use Doctrine\DBAL\Platforms\AbstractPlatform;
    use Doctrine\DBAL\Platforms\MySQL80Platform;

    trait Platform {
        private function getPlatform(): AbstractPlatform {
            return match("mysqli") {
                'pdo_mysql', 'mysqli' => new MySQL80Platform(),
            };
        }
    }
