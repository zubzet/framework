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

            $this->addOption(
                "environments-included",
                "i",
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                "Include seed folders/files after excludes are applied",
            );

            $this->addOption(
                "environments-excluded",
                "e",
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                "Exclude seed folders/files from execution",
            );

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $startTime = microtime(true);
            $out->writeln("<comment>Seeding started at: " . date("Y-m-d H:i:s") . "</comment>");

            $skipMigrations = $in->getOption("skip-migrations");
            $includedEnvironmentPaths = $in->getOption("environments-included");
            $excludedEnvironmentPaths = $in->getOption("environments-excluded");

            if(!$skipMigrations) {
                $out->writeln("<comment>Running migrations before seeding...</comment>");
                $this->resetDatabase($out);
            }

            $seedRoot = "./app/Database/seed";
            $seedFiles = model("z_migration")->getFiles($seedRoot);

            // Filter seed files based on include/exclude options
            $seedFiles = $this->filterSeedFiles(
                $seedFiles,
                $seedRoot,
                $excludedEnvironmentPaths,
                $includedEnvironmentPaths,
            );

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

        /**
         * Seed filtering always starts with all files.
         * Step 1: remove matching excluded paths.
         * Step 2: add matching included paths back in.
         */
        private function filterSeedFiles(array $seedFiles, string $seedRoot, array $excludedPaths, array $includedPaths): array {
            // No files -> no filtering needed
            if(empty($seedFiles)) return [];

            $selectedSeeds = [];

            foreach($seedFiles as $seedFile) {
                // Exclude any files that match the excluded paths first
                if($this->matchesAnySelector($seedFile, $excludedPaths, $seedRoot)) continue;

                $selectedSeeds[$seedFile] = true;
            }

            foreach($seedFiles as $seedFile) {
                // Then add back in any files that match the included paths, even if they were previously excluded
                if(!$this->matchesAnySelector($seedFile, $includedPaths, $seedRoot)) continue;

                $selectedSeeds[$seedFile] = true;
            }

            $filteredSeedFiles = [];

            foreach($seedFiles as $seedFile) {
                if(!isset($selectedSeeds[$seedFile])) continue;

                // Preserve the original order of the seed files as returned by getFiles()
                $filteredSeedFiles[] = $seedFile;
            }

            return $filteredSeedFiles;
        }

        private function matchesAnySelector(string $seedFile, array $selectors, string $seedRoot): bool {
            foreach($selectors as $selector) {
                if(!$this->matchesSelector($seedFile, (string) $selector, $seedRoot)) continue;
                return true;
            }

            return false;
        }

        private function matchesSelector(string $seedFile, string $selector, string $seedRoot): bool {
            $selector = trim($selector);
            if($selector === "") return false;

            // Normalize paths to ensure consistent matching
            $selectorPath = rtrim($seedRoot, "/") . "/" . ltrim($selector, "/");

            // If the selector is a file (e.g. "Environments/Prod/seed.sql"), it matches if the seed file path is exactly the same.
            if($seedFile === $selectorPath) return true;

            // Otherwise check if the seed file is within the selector folder (e.g. "Environments/Prod/" matches "Environments/Prod/seed.sql")
            return str_starts_with($seedFile, $selectorPath . "/");
        }
    }

?>