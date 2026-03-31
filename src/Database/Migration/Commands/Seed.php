<?php
    namespace ZubZet\Framework\Database\Migration\Commands;

    use Exception;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;
    use ZubZet\Framework\Database\Migration\Parser\SeedPHP;
    use ZubZet\Framework\Database\Migration\Parser\SeedSQL;

    final class Seed extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("db:seed");
            $this->setDescription("Execute a database seeding task.");

            $this->addOption(
                "skip-migrations",
                "s",
                InputOption::VALUE_NONE,
                "Runs the seeding process without running the migrations first.",
            );

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $startTime = microtime(true);
            $out->writeln("<comment>Seeding started at: " . date("Y-m-d H:i:s") . "</comment>");

            $skipMigrations = $in->getOption("skip-migrations");

            if(!$skipMigrations) {
                $out->writeln("<comment>Running migrations before seeding...</comment>");
                $this->resetDatabase($out);
            }

            $seedFiles = model("z_migration")->getFiles("./app/Database/seed");

            foreach($seedFiles as $seedFile) {
                try {
                    $this->importFile($seedFile, $out);
                    $out->writeln("<info>Successfully executed seed file: $seedFile</info>");
                } catch(Exception $e) {
                    $out->writeln("<error>Error executing seed file $seedFile: {$e->getMessage()}</error>");
                    return 1;
                }
            }

            $this->executeBufferedStatements($out);

            $elapsed = round(microtime(true) - $startTime, 2);
            $out->writeln("<comment>Seeding finished at: " . date("Y-m-d H:i:s") . " (took {$elapsed}s)</comment>");

            return 0;
        }

        private function resetDatabase(OutputInterface $out) {
            // Drop and recreate the database
            $dbName = db()->booter->dbname;
            $conn = db()->getDatabaseConnection();

            $conn->select_db('information_schema');
            db()->executeMultiQuery( "DROP DATABASE IF EXISTS `" . $dbName . "`; CREATE DATABASE $dbName;");
            $conn->select_db($dbName);

            // Execute the db:migrate command to recreate the schema
            $application = $this->getApplication();
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'db:migrate'
            ]);

            $application->run($input, $out);
        }

        private string $bufferedStatements = "";

        private function executeBufferedStatements(OutputInterface $out) {
            if(empty($this->bufferedStatements)) return;

            $out->writeln("<comment>Executing buffered SQL statements...</comment>");
            db()->executeMultiQuery($this->bufferedStatements);
            $this->bufferedStatements = "";
        }

        // Retrieve and execute SQL statements from a seed file
        private function importFile($filePath, OutputInterface $out) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if(!in_array($extension, ["sql", "php"])) {
                throw new Exception("Unsupported seed file type: $extension. Only .sql and .php files are allowed.");
            }

            if($extension == "php") {
                // Buffered PHP seed files to avoid multiple database calls
                $this->executeBufferedStatements($out);

                // Execute the PHP seed file
                $this->executePHPStatements($filePath);
                return;
            }

            $this->executeSQLStatements($filePath);
        }

        private function executePHPStatements($filePath) {
            $sqlStatements = (new SeedPHP())->loadPhpSeed($filePath);

            if(empty($sqlStatements)) return;

            foreach($sqlStatements as $query) {
                db()->execQuery($query);
            }
        }

        private function executeSQLStatements($filepath) {
            $sqlStatement = (new SeedSQL())->loadSqlFile($filepath);
            if(trim($sqlStatement) === "") return;

            // Buffer the SQL statements and execute them in batches to optimize performance
            $this->bufferedStatements .= "\n-- SEED FILE: $filepath\n$sqlStatement";
        }
    }

?>