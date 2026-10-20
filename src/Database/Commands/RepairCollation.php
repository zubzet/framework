<?php
    namespace ZubZet\Framework\Database\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;

    // Converts the framework tables (z_) to the framework collation; with --include-app-tables the application tables and the database default too
    final class RepairCollation extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("repair:database-collation");
            $this->setDescription(
                "Convert the framework tables (z_) to " . Connection::COLLATION . ". Rewrites each table that differs, which can take a while."
            );

            $this->addOption(
                "include-app-tables",
                null,
                InputOption::VALUE_NONE,
                "Convert the application tables too and set the database default to " . Connection::COLLATION . ".",
            );

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $includeAppTables = $in->getOption("include-app-tables");
            $tables = model("z_migration")->tablesNotInCollation(Connection::COLLATION, $includeAppTables);

            if($includeAppTables) {
                $database = str_replace("`", "``", config("dbname"));
                db()->exec("ALTER DATABASE `$database` CHARACTER SET " . Connection::CHARSET . " COLLATE " . Connection::COLLATION);
                $out->writeln("\tDatabase default set to " . Connection::COLLATION);
            }

            if(empty($tables)) {
                $out->writeln("<info>Every table already uses " . Connection::COLLATION . ". Nothing to do.</info>");
                return Command::SUCCESS;
            }

            foreach($tables as $table) {
                $out->writeln("\tConverting {$table}");
                model("z_migration")->convertTableToCollation($table, Connection::CHARSET, Connection::COLLATION);
            }

            $out->writeln("<info>Done: " . \count($tables) . " table(s) converted to " . Connection::COLLATION . ".</info>");
            return Command::SUCCESS;
        }
    }

?>
