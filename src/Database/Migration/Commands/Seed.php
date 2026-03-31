<?php
    namespace ZubZet\Framework\Database\Migration\Commands;

    use Cake\Database\Query;
    use Exception;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
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
                    $this->importFile($seedFile);
                    $out->writeln("<info>Successfully executed seed file: $seedFile</info>");
                } catch(Exception $e) {
                    $out->writeln("<error>Error executing seed file $seedFile: {$e->getMessage()}</error>");
                    return 1;
                }
            }

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

        // Retrieve and execute SQL statements from a seed file
        private function importFile($filePath) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Load SQL statements based on file type
            match($extension) {
                'sql' => $this->executeSQLStatements($filePath),
                'php' => $this->executePHPStatements($filePath),
            };
        }

        private function executePHPStatements($filePath) {
            $sqlStatements = (new SeedPHP())->loadPhpSeed($filePath);

            if(empty($sqlStatements)) return;

            foreach($sqlStatements as $query) {
                if(!($query instanceof Query)) continue;
                db()->execQuery($query);
            }
        }

        private function executeSQLStatements($filepath) {
            $sqlStatements = (new SeedSQL())->loadSqlFile($filepath);

            if(empty($sqlStatements)) return;
            $fullQuery = "";

            foreach($sqlStatements as $sql) {
                if(!is_string($sql)) continue;
                $fullQuery .= $sql . ";";
            }

            if(empty($fullQuery)) return;

            db()->executeMultiQuery($fullQuery);
        }
    }

?>