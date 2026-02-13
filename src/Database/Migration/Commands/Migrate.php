<?php
    namespace ZubZet\Framework\Database\Migration\Commands;

    use Exception;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;

    final class Migrate extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("db:migrate");
            $this->setDescription("Execute all outstanding database migrations.");

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
                "Run migrations in dry mode without applying changes",
            );

            $this->addOption(
                "force",
                "f",
                InputOption::VALUE_NONE,
                "Ignore if any migrations were skipped and proceed with the import",
            );

            $this->addOption(
                "exclude-external",
                null,
                InputOption::VALUE_NONE,
                "Exclude external migrations from the import",
            );

            $this->addOption(
                "enforce-external-timeline",
                null,
                InputOption::VALUE_NONE,
                "Enforce that external migrations"
            );

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            model("z_migration")->ensureMigrationTablesExist();

            $dryMode = $in->getOption("dry");
            $force = $in->getOption("force");
            $excludeExternal = $in->getOption("exclude-external");
            $enforceExternalTimeline = $in->getOption("enforce-external-timeline");

            $includedEnvironments = $in->getOption("environments-included");
            $excludedEnvironments = $in->getOption("environments-excluded");

            // Check lock status
            if(model("z_migration")->isLocked()) {
                $out->writeln("<error>Migrations are currently locked. Aborting import.\n"
                . "Please check if another migration process is running, any migrations need to be imported manually, or if the lock needs to be cleared manually.</error>");
                return 1;
            }

            // Fetch and prepare migrations
            $dbMigrationsRaw = array_column(model("z_migration")->getExecutedMigrations(), 'migration_name');
            $dbMigrations = model("z_migration")->sortMigrations($dbMigrationsRaw);

            $zubzetMigrations = model("z_migration")->getFiles(zubzet()->z_framework_root . "IncludedComponents/database/Migration");

            $fileMigrationsRaw = model("z_migration")->getFiles("./app/Database/migrations");

            if(!$excludeExternal) {
                $fileMigrationsRaw = array_merge(
                    $fileMigrationsRaw,
                    $zubzetMigrations
                );
            }

            $fileMigrations = model("z_migration")->sortMigrations($fileMigrationsRaw);

            // Validate migration integrity (detect skipped files)
            $lastDbMigration = end($dbMigrations);
            $skippedMigrations = [];

            if($lastDbMigration !== false) {
                $dbLookup = [];
                foreach($dbMigrations as $migration) {
                    $dbLookup[$migration->name] = true;
                }

                foreach($fileMigrations as $fileMigration) {
                    // Only check files older than the last executed migration
                    if($fileMigration->date > $lastDbMigration->date) continue;

                    $isZubZetMigration = in_array($fileMigration->filename, $zubzetMigrations);
                    if($isZubZetMigration && !$enforceExternalTimeline) continue;

                    // If it exists in DB, it's fine
                    if(isset($dbLookup[$fileMigration->name])) continue;

                    $skippedMigrations[] = $fileMigration;
                }
            }

            // Handle skipped migrations warning/abort
            if(!empty($skippedMigrations)) {
                $out->writeln("<comment>Warning: The following migrations were skipped:</comment>");
                foreach($skippedMigrations as $skipped) {
                    $out->writeln("<comment>- {$skipped->name}</comment>");
                }

                if(!$force) {
                    $out->writeln("<error>Aborting import due to skipped migrations. Use --force to ignore.</error>");
                    return 1;
                }
            }

            // Determine pending migrations
            $executedNames = array_map(fn($m) => $m->name, $dbMigrations);

            $pendingMigrations = array_values(array_filter(
                $fileMigrations,
                fn($f) => !in_array($f->name, $executedNames)
            ));

            // No pending migrations
            if(empty($pendingMigrations)) {
                $out->writeln("<info>No pending migrations found.</info>");
                return 0;
            }

            // Lock table if we are going to write
            if(!$dryMode) model("z_migration")->lockMigrations();
            $out->writeln("<info>Table was locked.</info>");

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

                    if($file->skip) {
                        $out->writeln("<info>Skipping migration (marked to skip): {$file->filename}</info>");

                        if($dryMode) continue;
                        model("z_migration")->markAsExecuted($file->filename, $file->date->format("Y-m-d"), $file->version);

                        continue;
                    }

                    if($file->manual) {
                        $out->writeln("<error>Migration requires manual execution: {$file->filename}. \n"
                            . "Please execute the necessary SQL statements manually and run the db:sync command to synchronize the system.</error>");
                        return 0;
                    }

                    $out->writeln("<info>Importing migration: {$file->filename}</info>");

                    // Do not execute in dry mode
                    if($dryMode) continue;

                    // Execute SQL
                    try {
                        $this->executeSqlBuffer($file->sqlBuffer);
                        model("z_migration")->markAsExecuted($file->filename, $file->date->format("Y-m-d"), $file->version);
                    } catch(Exception $e) {
                        $out->writeln("<error>Error importing {$file->filename}: " . $e->getMessage() . "</error>");
                        return 1;
                    }
                }

            } catch(Exception $e) {
                $out->writeln("<error>Migration import failed: " . $e->getMessage() . "</error>");
            }

            if(!$dryMode) model("z_migration")->unlockMigrations();
            $out->writeln("<info>Table was unlocked.</info>");

            return 0;
        }

        private function executeSqlBuffer(array $statements): void {
            foreach($statements as $sql) {
                if(empty(trim($sql))) continue;

                // Split into individual statements by semicolon
                $parts = array_filter(
                    array_map('trim', explode(";", $sql)), 
                    fn($s) => !empty($s)
                );

                // Execute each individual statement
                foreach($parts as $query) {
                    db()->exec($query);
                }
            }
        }
    }