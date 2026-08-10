<?php

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class AppGuestbookGreetCommand extends Command {

        protected function configure(): void {
            $this->setName("guestbook:greet");
            $this->setDescription("Userspace override of the module's guestbook:greet.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            // Same command name as the guestbook module's GuestbookGreetCommand,
            // different class: the userspace registration must win.
            $out->writeln("greet-from-app");
            return Command::SUCCESS;
        }
    }

?>
