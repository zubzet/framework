<?php
    namespace ZubZet\Framework\Testing\Coverage\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Testing\Coverage\Collector;

    final class End extends Command {

        protected function configure(): void {
            $this->setName('testing:coverage:end');
            $this->setDescription('Stop the coverage collection session and generate a report');
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            if(!file_exists(Collector::$sessionLocation)) {
                $out->writeln("<error>No active coverage collection session.</error>");
                $out->writeln("Start a session with <info>testing:coverage:start</info> before ending it.");
                return Command::FAILURE;
            }

            unlink(Collector::$sessionLocation);
            $out->writeln("Coverage collection <info>successfully ended</info>.");

            return Command::SUCCESS;
        }
    }

?>