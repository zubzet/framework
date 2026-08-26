<?php

    namespace ZubZet\Framework\Testing\Coverage\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Output\OutputInterface;

    /**
     * @codeCoverageIgnore Coverage tooling; cannot measure itself.
     */
    abstract class CoverageCommand extends Command {

        /** Prints the missing phpunit/php-code-coverage error and returns the failure exit code. */
        protected function missingCoverageLibrary(OutputInterface $out): int {
            $out->writeln("<error>This command requires the phpunit/php-code-coverage library, which is not installed.</error>");
            $out->writeln("It is a development dependency of ZubZet. Install it with <info>composer require --dev \"phpunit/php-code-coverage:9.*\"</info>.");
            return Command::FAILURE;
        }
    }

?>
