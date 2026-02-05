<?php
    namespace ZubZet\Framework\Testing\Coverage\Commands;

    use CoverageCollector;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Testing\Coverage\Collector;

    final class Start extends Command {

        protected function configure(): void {
            $this->setName('testing:coverage:start');
            $this->setDescription('Start a session to collect coverage data');
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            if(file_exists(Collector::$sessionLocation)) {
                $out->writeln("<error>Coverage collection is already active.</error>");
                $out->writeln("End the current session with <info>testing:coverage:end</info> before starting a new one.");
                return Command::FAILURE;
            }

            Collector::$sessionId = uniqid();
            file_put_contents(Collector::$sessionLocation, Collector::$sessionId);
            $out->writeln("Coverage collection <info>successfully started</info> as session '" . Collector::$sessionId . "'");
            return Command::SUCCESS;
        }
    }

?>