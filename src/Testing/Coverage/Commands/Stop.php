<?php

    namespace ZubZet\Framework\Testing\Coverage\Commands;

    use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlReport;
    use ZubZet\Framework\Testing\Coverage\TextReport;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Testing\Coverage\Collector;

    final class Stop extends Command {

        protected function configure(): void {
            $this->setName('testing:coverage:stop');
            $this->setDescription('Stop the coverage collection session and generate a report');

            $this->addOption('cli', 'c', null, 'Output the coverage report to stdout instead of generating an HTML report.');
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $isCli = $in->getOption('cli');

            if(!Collector::isActive()) {
                $out->writeln("<error>No active coverage collection session.</error>");
                $out->writeln("Start a session with <info>testing:coverage:start</info> before ending it.");
                return Command::FAILURE;
            }

            $out->writeln("Generating coverage report...");

            $coverage = Collector::merge();

            if(is_null($coverage)) {
                $out->writeln("<comment>No coverage data collected.</comment>");
                Collector::cleanup();
                return Command::SUCCESS;
            }

            try {
                if($isCli) {
                    $out->write((new TextReport())->process($coverage));
                } else {
                    $reportDir = Collector::$dataDirectory . 'report/';
                    (new HtmlReport)->process($coverage, $reportDir);
                    $out->writeln("Report generated at <info>{$reportDir}</info>");
                }

                register_shutdown_function(function() {
                    Collector::cleanup();
                });
            } catch(\Exception $e) {
                $out->writeln("<error>Failed to generate coverage report: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }

            $out->writeln("Coverage collection <info>successfully ended</info>.");

            return Command::SUCCESS;
        }
    }

?>