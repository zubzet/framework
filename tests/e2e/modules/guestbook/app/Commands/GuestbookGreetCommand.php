<?php

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class GuestbookGreetCommand extends Command {

        protected function configure(): void {
            $this->setName("guestbook:greet");
            $this->setDescription("Greet from the guestbook module.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            // The e2e app ships a different class with the same command name;
            // userspace wins, so this output must never appear.
            $out->writeln("greet-from-module");
            return Command::SUCCESS;
        }
    }

?>
