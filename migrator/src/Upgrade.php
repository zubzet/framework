<?php

    namespace ZubZet\Tooling;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Style\SymfonyStyle;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class Upgrade extends Command {
        protected static $defaultName = 'upgrade';
        protected static $defaultDescription = 'Upgrade a ZubZet project from one version to another.';

        public array $skipSteps = [];

        /** @var string[] */
        private array $versions = [
            "0.10.0",
            "0.11.0",
            "1.0.0",
        ];

        protected function configure(): void {
            // Arguments: FROM and TO version (both required)
            $this->addArgument('location', InputArgument::REQUIRED, 'Location of the project to upgrade');
            $this->addArgument('from', InputArgument::REQUIRED, 'Version to upgrade from');
            $this->addArgument('to', InputArgument::REQUIRED, 'Version to upgrade to');

            // Options:
            $this->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry-run: show what would be executed without making changes');
            $this->addOption(
                'skip',
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Steps to skip (can be used multiple times, e.g. -s 1 -s 4)',
            );

            // Sort versions in ascending order to ensure proper upgrade sequencing
            usort($this->versions, 'version_compare');
        }

        public InputInterface $input;
        public OutputInterface $output;
        public bool $dry = true;

        public string $rootDir;

        public SymfonyStyle $io;

        public function execute(InputInterface $input, OutputInterface $output): int {
            $this->input = $input;
            $this->output = $output;

            $this->rootDir = realpath(__DIR__ . '/..');

            $this->io = new SymfonyStyle($input, $output);

            $location = (string) $input->getArgument('location');
            $from = (string) $input->getArgument('from');
            $to = (string) $input->getArgument('to');

            $this->dry = (bool) $input->getOption('dry');
            $this->skipSteps = (array) $input->getOption('skip');

            if($this->dry) {
                $output->writeln('Dry-run mode: no changes will be made.');
            }

            // Switch to the desired project directory
            if(!is_dir($location)) {
                $output->writeln(sprintf('Invalid project location: <error>%s</error>', $location));
                return Command::INVALID;
            }
            chdir($location);

            // Validate provided versions exist
            $fromIndex = array_search($from, $this->versions, true);
            $toIndex = array_search($to, $this->versions, true);

            if(false === $fromIndex || false === $toIndex) {
                $type = !$fromIndex ? 'from' : 'to';
                $version = !$fromIndex ? $from : $to;
                $output->writeln(sprintf('Unknown "%s" version: <error>%s</error>', $type, $version));
                $output->writeln('<comment>Available versions:</comment> ' . implode(', ', $this->versions));
                return Command::INVALID;
            }

            // Ensure from < to
            if ($fromIndex >= $toIndex) {
                $output->writeln(sprintf(
                    '<error>Invalid range: "from" (%s) must be earlier than "to" (%s)</error>',
                    $from,
                    $to,
                ));
                return Command::INVALID;
            }

            // Determine the sequence of upgrade target versions (exclusive of $from, inclusive of $to)
            $steps = array_slice(
                $this->versions,
                $fromIndex + 1,
                $toIndex - $fromIndex,
            );

            $this->io->title(sprintf(
                'Planning upgrade from <info>%s</info> to <info>%s</info> (in %d step%s)',
                $from,
                $to,
                count($steps),
                1 === count($steps) ? '' : 's',
            ));

            // Run each step in order
            foreach($steps as $i => $target) {
                $current = $steps[$i - 1] ?? $from;

                // Determine the migration class name for the target version
                $suffix = str_replace('.', '_', $target);
                $namespacedClass = "ZubZet\\Tooling\\Version\\V{$suffix}";
                $class = null;
                if(class_exists($namespacedClass)) $class = $namespacedClass;

                // Handle missing migration class
                if(is_null($class)) {
                    $output->writeln(sprintf(
                        '<error>No migration class found for target version %s (tried V%s). Aborting.</error>',
                        $target,
                        str_replace('.', '_', $target),
                    ));
                    return Command::FAILURE;
                }

                $this->io->writeln("");
                $this->io->section(sprintf(
                    'Upgrading <info>%s</info> -> <info>%s</info>',
                    $current,
                    $target,
                ));

                $migration = new $class($this, $target);
                $result = $migration->upgrade();

                // If the migration returns false, consider it failed
                if(false === $result) {
                    $output->writeln(sprintf(
                        '<error>Migration %s -> %s reported failure.</error>',
                        $current,
                        $target,
                    ));
                    return Command::FAILURE;
                }
            }

            $this->io->newLine();
            $this->io->title('Upgrade complete');

            return Command::SUCCESS;
        }
    }

?>