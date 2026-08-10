<?php

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class GuestbookStatsCommand extends Command {

        protected function configure(): void {
            $this->setName("guestbook:stats");
            $this->setDescription("Print the number of guestbook entries.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $count = model("GuestbookStats")->countEntries();
            $out->writeln("guestbook-entries:$count");
            return Command::SUCCESS;
        }
    }

?>
