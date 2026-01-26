<?php

    use Doctrine\DBAL\DriverManager;
    use Doctrine\DBAL\Platforms\MySQL\CharsetMetadataProvider;
    use Doctrine\DBAL\Platforms\MySQL\CollationMetadataProvider;
    use Doctrine\DBAL\Platforms\MySQL\Comparator;
    use Doctrine\DBAL\Platforms\MySQL\DefaultTableOptions;
    use Doctrine\DBAL\Platforms\MySQL80Platform;
    use Doctrine\DBAL\Schema\Column;
    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\DBAL\Schema\Table;
    use Doctrine\DBAL\Types\Type;

    class TestController extends z_controller {

        /*
        KEIN TIMESTAMP
        */

        public function action_test(Request $req, Response $res) {
            $this->t0();
        }

        private function t0() {
            $connection = DriverManager::getConnection([
                'dbname' => 'app',
                'user' => 'app',
                'password' => 'app_password',
                'host' => 'database',
                'port' => 3306,
                'driver' => 'mysqli',
            ]);

            $schemaManager = $connection->createSchemaManager();

            $fromSchema = $schemaManager->introspectSchema();
            $toSchema = clone $fromSchema;

            $table = $toSchema->getTable('z_user');

            $table->addColumn('username', 'string', ['length' => 64, 'notnull' => true]);
            $table->dropColumn('te');

            $diff = $schemaManager->createComparator()->compareSchemas($fromSchema, $toSchema);

            foreach($connection->getDatabasePlatform()->getAlterSchemaSQL($diff) as $sql) {
                echo $sql . ";\n";
            }
        }


        private function t1() {
            $schema = new Schema();
            $users = $schema->createTable('users');

            $users->addColumn('id', 'integer', ['autoincrement' => true]);
            $users->addColumn('username', 'string', ['length' => 32]);
            $users->setPrimaryKey(['id']);
            $users->renameColumn('username', 'user_name');
            $users->dropColumn('test');

            $platform = new MySQL80Platform();

            $queries = $schema->toSql($platform);

            print_r($queries[0]);
        }



        private function t3() {
            $platform = new MySQL80Platform();

            $table = new Table('z_user');
            $table->addColumn('test', 'string', ['length' => 255]);

            $addUpdated = new Column(
                'updated',
                Type::getType("datetime"),
                [
                    'columnDefinition' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                    'notnull' => true,
                ]
            );

            $diff = new \Doctrine\DBAL\Schema\TableDiff(
                $table,
                [$addUpdated],
                [],
                [
                    new Column('old_column', Type::getType('string'))
                ],
                []
            );

            $sql = $platform->getAlterTableSQL($diff);

            print_r($sql);
        }



        private function t2() {
            $platform = new MySQL80Platform();

            $table = new Table('z_user');

            $addUpdated = new Column(
                'updated',
                Type::getType("datetime"),
                [
                    'columnDefinition' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                    'notnull' => true,
                ]
            );

            $diff = new \Doctrine\DBAL\Schema\TableDiff(
                $table,
                [$addUpdated],
                [],
                ['test'],
                []
            );

            $sql = $platform->getAlterTableSQL($diff);

            print_r($sql);
        }
    }

?>