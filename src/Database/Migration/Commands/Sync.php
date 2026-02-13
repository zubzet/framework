<?php
    namespace ZubZet\Framework\Database\Migration\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;

    final class Sync extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("db:sync");
            $this->setDescription("Synchronize migration state without execution.");

            $this->addOption(
                'start',
                null,
                InputOption::VALUE_REQUIRED,
                'Date from which migrations should be synced (format: YYYY-MM-DD)',
            );

            $this->addOption(
                'startVersion',
                null,
                InputOption::VALUE_REQUIRED,
                'Version from which migrations should be synced',
            );

            $this->addOption(
                'end',
                null,
                InputOption::VALUE_REQUIRED,
                'Date until which migrations should be synced (format: YYYY-MM-DD)',
            );

            $this->addOption(
                'endVersion',
                null,
                InputOption::VALUE_REQUIRED,
                'Version from which migrations should be synced',
            );

            $this->addOption(
                "environments-included",
                "i",
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                "Specify additional environments to include migrations for",
            );

            $this->addOption(
                "environments-excluded",
                "e",
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                "Specify additional environments to exclude migrations for",
            );

            $this->addOption(
                "dry",
                "d",
                InputOption::VALUE_NONE,
                "Run sync in dry mode without applying changes",
            );

            $this->addOption(
                "include-external",
                null,
                InputOption::VALUE_NONE,
                "Include external migrations in the sync",
            );

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            model("z_migration")->ensureMigrationTablesExist();

            $startDate = $in->getOption("start");
            $startVersion = $in->getOption("startVersion");

            $endDate = $in->getOption("end");
            $endVersion = $in->getOption("endVersion");

            $includedEnvironments = $in->getOption("environments-included");
            $excludedEnvironments = $in->getOption("environments-excluded");

            $dryMode = $in->getOption("dry");
            $includeExternal = $in->getOption("include-external");

            // Validate options
            if($startVersion && !$startDate) {
                $out->writeln("<error>Cannot use start version without specifying a start date.</error>");
                return 1;
            }

            if($startDate) {
                $startDateObj = \DateTime::createFromFormat('Y-m-d', $startDate);

                if(!$startDateObj || $startDateObj->format('Y-m-d') !== $startDate) {
                    $out->writeln("<error>Invalid start date format. Expected format: YYYY-MM-DD.</error>");
                    return 1;
                }
            }

            if($endVersion && !$endDate) {
                $out->writeln("<error>Cannot use end version without specifying an end date.</error>");
                return 1;
            }

            if($endDate) {
                $endDateObj = \DateTime::createFromFormat('Y-m-d', $endDate);

                if(!$endDateObj || $endDateObj->format('Y-m-d') !== $endDate) {
                    $out->writeln("<error>Invalid end date format. Expected format: YYYY-MM-DD.</error>");
                    return 1;
                }
            }

            // Fetch and prepare migrations
            $dbMigrationsRaw = array_column(model("z_migration")->getExecutedMigrations(), 'migration_name');
            $dbMigrations = model("z_migration")->sortMigrations($dbMigrationsRaw);

            $fileMigrationsRaw = model("z_migration")->getFiles("./app/Database/migrations");

            if($includeExternal) {
                $fileMigrationsRaw = array_merge(
                    $fileMigrationsRaw,
                    model("z_migration")->getFiles(zubzet()->z_framework_root . "IncludedComponents/database/Migration")
                );
            }
            $fileMigrations = model("z_migration")->sortMigrations($fileMigrationsRaw);

            $executedNames = array_map(fn($m) => $m->name, $dbMigrations);

            $pendingMigrations = array_values(array_filter(
                $fileMigrations,
                fn($f) => !in_array($f->name, $executedNames)
            ));

            // No pending migrations
            if(empty($pendingMigrations)) {
                $out->writeln("<info>No pending migrations to synchronized found.</info>");
            }

            try {

                foreach($pendingMigrations as $file) {
                    $file->extractData();

                    // Check specific environment mismatch
                    if(!empty($includedEnvironments) && !in_array($file->environment, $includedEnvironments)) {
                        $out->writeln("<info>Skipping migration (not in included environments): {$file->filename}</info>");
                        continue;
                    }

                    if(!empty($excludedEnvironments) && in_array($file->environment, $excludedEnvironments)) {
                        $out->writeln("<info>Skipping migration (in excluded environments): {$file->filename}</info>");
                        continue;
                    }

                    // Check start date/version
                    if($startDate) {
                        if($file->date < $startDateObj) {
                            $out->writeln("<info>Skipping migration (before start date): {$file->filename}</info>");
                            continue;
                        }

                        // Checking the version (if date matches)
                        if($file->date == $startDateObj && $startVersion && $file->version < $startVersion) {
                            $out->writeln("<info>Skipping migration (before start version): {$file->filename}</info>");
                            $out->writeln("{$file->version} {$file->name} {$startVersion}</info>");
                            continue;
                        }
                    }

                    // Check end date/version
                    if($endDate) {
                        if($file->date > $endDateObj) {
                            $out->writeln("<info>Skipping migration (after end date): {$file->filename}</info>");
                            continue;
                        }

                        // Checking the version (if date matches)
                        if($file->date == $endDateObj && $endVersion && $file->version > $endVersion) {
                            $out->writeln("<info>Skipping migration (after end version): {$file->filename}</info>");
                            continue;
                        }
                    }

                    $out->writeln("<info>Synchronizing migration: {$file->filename}</info>");

                    // Do not execute in dry mode
                    if($dryMode) continue;

                    model("z_migration")->markAsExecuted($file->filename, $file->date->format("Y-m-d"), $file->version);
                }

            } catch(\Exception $e) {
                $out->writeln("<error>Synchronization failed: {$e->getMessage()}</error>");
                return 1;
            }

            if(!$dryMode) {
                model("z_migration")->unlockMigrations();
                $out->writeln("<info>Table was unlocked.</info>");
            }

            return 0;
        }

    }

?>