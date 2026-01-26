<?php
    namespace ZubZet\Framework\Database\Migration\Commands;

    use Cake\Database\Query;
    use Exception;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\BufferedOutput;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;
    use ZubZet\Framework\Database\Migration\Parser\SeedPHP;
    use ZubZet\Framework\Database\Migration\Parser\SeedSQL;

    final class Seed extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("db:seed");
            $this->setDescription("Execute a database seeding task.");

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $this->resetDatabase();

            $seedFiles = model("z_migration")->getFiles("./app/Database/seed");

            foreach($seedFiles as $seedFile) {
                try {
                    $this->importFile($seedFile);
                    $out->writeln("<info>Successfully executed seed file: $seedFile</info>");
                } catch(Exception $e) {
                    $out->writeln("<error>Error executing seed file $seedFile: {$e->getMessage()}</error>");
                    return 1;
                }
            }

            return 0;
        }

        private function resetDatabase() {
            // Drop and recreate the database
            $dbName = db()->booter->dbname;
            $conn = db()->getDatabaseConnection();

            $conn->select_db('information_schema');
            db()->executeMultiQuery( "DROP DATABASE IF EXISTS `" . $dbName . "`; CREATE DATABASE $dbName;");
            $conn->select_db($dbName);

            // Execute the db:import command to recreate the schema
            $application = $this->getApplication();
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'db:import'
            ]);

            $output = new BufferedOutput();
            $application->run($input, $output);
        }

        // Retrieve and execute SQL statements from a seed file
        private function importFile($filePath) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Load SQL statements based on file type
            $sqlStatements = match($extension) {
                'sql' => (new SeedSQL())->loadSqlFile($filePath),
                'php' => (new SeedPHP())->loadPhpSeed($filePath),
            };

            if(empty($sqlStatements)) return;

            // Execute the SQL statements
            $this->executeSqlBuffer($sqlStatements);
        }

        // Execute a buffer of SQL statements
        private function executeSqlBuffer(array $statements): void {
            $fullQuery = "";

            foreach($statements as $sql) {
                if(is_string($sql)) {
                    $fullQuery .= $sql . ";";
                    continue;
                }

                if($sql instanceof Query) {
                    $fullQuery .= db()->extractQueryBuilderSQL($sql) . ";";
                    continue;
                }
            }

            if(empty($fullQuery)) return;

            db()->executeMultiQuery($fullQuery);
        }
    }

?>