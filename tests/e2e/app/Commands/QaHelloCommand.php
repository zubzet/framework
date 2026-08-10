<?php

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class QaHelloCommand extends Command {

        protected function configure(): void {
            $this->setName("qa:hello");
            $this->setDescription("Prove userspace convention commands load.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $out->writeln("hello-from-userspace");
            return Command::SUCCESS;
        }
    }

?>
