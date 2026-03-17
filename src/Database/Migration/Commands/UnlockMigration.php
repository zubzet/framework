<?php
    namespace ZubZet\Framework\Database\Migration\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;

    final class UnlockMigration extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("db:unlock-migration");
            $this->setDescription("Unlock the migration table if it is locked.");

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            model("z_migration")->ensureMigrationTablesExist();

            if(!model("z_migration")->isLocked()) {
                $out->writeln("<comment>Migration table is not locked.</comment>");
                return 0;
            }

            model("z_migration")->unlockMigrations();
            $out->writeln("<info>Migration table unlocked.</info>");

            return 0;
        }
    }

?>