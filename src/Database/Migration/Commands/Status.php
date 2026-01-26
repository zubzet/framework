<?php
    namespace ZubZet\Framework\Database\Migration\Commands   ;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;

    final class Status extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("db:status");
            $this->setDescription("Show the current migration status");

            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $isLocked = model("z_migration")->isLocked();

            $out->writeln("Migration Lock Status: <info>" . ($isLocked ? "LOCKED" : "UNLOCKED") . "</info>");
            return $isLocked ? Command::SUCCESS : Command::FAILURE;
        }
    }

?>