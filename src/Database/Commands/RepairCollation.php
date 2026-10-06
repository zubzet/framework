<?php
    namespace ZubZet\Framework\Database\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;

    // Converts every table of the database, framework and application alike, to the framework collation
    final class RepairCollation extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("repair:database-collation");
            $this->setDescription(
                "Convert every table of the database to " . Connection::COLLATION . ". Rewrites each table that differs, which can take a while."
            );

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $tables = model("z_migration")->tablesNotInCollation(Connection::COLLATION);

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
