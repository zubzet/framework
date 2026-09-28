<?php
    namespace ZubZet\Framework\Support\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    final class Install extends Command {

        // Hardcoded for now, mirrors the zubzet/zubzet skeleton.
        private const FOLDERS = [
            "app",
            "app/Controllers",
            "app/Models",
            "app/Views",
            "app/Views/layout",
            "app/Routes",
            "app/Database",
            "app/Database/migrations",
            "app/Database/seed",
            "webroot",
            "webroot/assets",
            "webroot/assets/css",
            "webroot/assets/js",
            "z_config",
        ];

        protected function configure(): void {
            $this->setName("install");
            $this->setDescription("Builds the project folder structure.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            foreach(self::FOLDERS as $folder) {
                if(is_dir($folder)) {
                    $out->writeln("exists:  {$folder}");
                    continue;
                }

                if(!mkdir($folder)) {
                    $out->writeln("<error>failed:  {$folder}</error>");
                    return Command::FAILURE;
                }
                $out->writeln("<info>created: {$folder}</info>");
            }

            return Command::SUCCESS;
        }

    }
