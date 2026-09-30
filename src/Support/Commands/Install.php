<?php
    namespace ZubZet\Framework\Support\Commands;

    use RecursiveIteratorIterator;
    use RecursiveDirectoryIterator;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
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
            "packaging",
            "packaging/docker",
        ];

        // Files copied from the framework's stubs/project, relative to the project root.
        private const STUBS = __DIR__ . "/../../../stubs/project";

        // Test only: the fork branch, switch back to "zubzet/framework" once merged.
        private const PACKAGE = "qtnoe/zubzet-framework:dev-feat/install-command";

        protected function configure(): void {
            $this->setName("install");
            $this->setDescription("Builds a new ZubZet project.");

            $this->addArgument("path", InputArgument::REQUIRED, "The project folder to build the project in.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $path = rtrim((string) $in->getArgument("path"), "/");
            if(!is_dir($path)) {
                $out->writeln("<error>Folder does not exist: {$path}</error>");
                return Command::FAILURE;
            }

            foreach(self::FOLDERS as $folder) {
                if(is_dir("{$path}/{$folder}")) {
                    $out->writeln("exists:  {$folder}");
                    continue;
                }

                if(!mkdir("{$path}/{$folder}")) {
                    $out->writeln("<error>failed:  {$folder}</error>");
                    return Command::FAILURE;
                }
                $out->writeln("<info>created: {$folder}</info>");
            }

            $stubs = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::STUBS, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach($stubs as $stub) {
                $file = substr($stub->getPathname(), strlen(self::STUBS) + 1);

                if(file_exists("{$path}/{$file}")) {
                    $out->writeln("exists:  {$file}");
                    continue;
                }

                if(!copy($stub->getPathname(), "{$path}/{$file}")) {
                    $out->writeln("<error>failed:  {$file}</error>");
                    return Command::FAILURE;
                }
                $out->writeln("<info>created: {$file}</info>");
            }

            chmod("{$path}/zubzet", 0755);
            chmod("{$path}/project.sh", 0755);

            $out->writeln("<info>composer require " . self::PACKAGE . "</info>");
            // The caller's COMPOSER_VENDOR_DIR points at the framework's vendor, not the new project's.
            passthru("env -u COMPOSER_VENDOR_DIR composer require " . escapeshellarg(self::PACKAGE) . " --no-interaction --working-dir=" . escapeshellarg($path), $exitCode);
            if($exitCode !== 0) {
                $out->writeln("<error>composer require failed</error>");
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        }

    }
