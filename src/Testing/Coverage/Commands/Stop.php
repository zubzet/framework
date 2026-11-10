<?php

    namespace ZubZet\Framework\Testing\Coverage\Commands;

    use SebastianBergmann\CodeCoverage\CodeCoverage;
    use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlReport;
    use ZubZet\Framework\Testing\Coverage\TextReport;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Testing\Coverage\Collector;

    /**
     * @codeCoverageIgnore Runs after measurement ends; coverage cannot record this.
     */
    final class Stop extends CoverageCommand {

        protected function configure(): void {
            $this->setName('testing:coverage:stop');
            $this->setDescription('Stop the coverage collection session and generate a report');

            $this->addOption('cli', 'c', null, 'Output the coverage report to stdout instead of generating an HTML report.');
            $this->addOption('merge', 'm', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Coverage file of a PHPUnit run (--coverage-php) to include in the report. Can be given several times.');
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $isCli = $in->getOption('cli');

            if(!Collector::hasCoverageLibrary()) {
                return $this->missingCoverageLibrary($out);
            }

            if(!Collector::isActive()) {
                $out->writeln("<error>No active coverage collection session.</error>");
                $out->writeln("Start a session with <info>testing:coverage:start</info> before ending it.");
                return Command::FAILURE;
            }

            $out->writeln("Generating coverage report...");

            $coverage = Collector::merge();

            foreach($in->getOption('merge') as $file) {
                if(!is_file($file)) {
                    $out->writeln("<error>Coverage file to merge not found: {$file}</error>");
                    return Command::FAILURE;
                }

                // PHPUnit writes a PHP file that returns the coverage object
                $external = require $file;
                if(!$external instanceof CodeCoverage) {
                    $out->writeln("<error>Not a coverage file written by PHPUnit: {$file}</error>");
                    return Command::FAILURE;
                }

                // The HTML report needs PHPUnit itself to colour a test by
                // its result. An application does not have it installed, so
                // the tests are only named.
                $external->setTests(array_map(
                    fn(array $test) => ['fromTestcase' => false] + $test,
                    $external->getTests()
                ));

                if(is_null($coverage)) {
                    $coverage = $external;
                    continue;
                }

                $coverage->merge($external);
            }

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
                    $out->write((new TextReport())->summary($coverage));
                    $out->writeln("Report generated at <info>{$reportDir}</info>");
                }

                register_shutdown_function(function() {
                    Collector::cleanup();
                });
            } catch(\Throwable $e) {
                $out->writeln("<error>Failed to generate coverage report: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }

            $out->writeln("Coverage collection <info>successfully ended</info>.");

            return Command::SUCCESS;
        }
    }

?>