<?php
    namespace ZubZet\Framework\Support\Commands;

    use RecursiveIteratorIterator;
    use RecursiveDirectoryIterator;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    final class Install extends Command {

        // The project template, copied as is into the project root.
        private const STUBS = __DIR__ . "/../../../stubs/project";

        // Test only: the local checkout via path repository, switch back to "zubzet/framework" once merged.
        private const PACKAGE = "zubzet/framework:*@dev";

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

            // SELF_FIRST: folders come before their contents, so they exist when the files are copied.
            $stubs = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::STUBS, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach($stubs as $stub) {
                $file = substr($stub->getPathname(), strlen(self::STUBS) + 1);

                if(file_exists("{$path}/{$file}")) {
                    $out->writeln("exists:  {$file}");
                    continue;
                }

                $created = $stub->isDir() ? mkdir("{$path}/{$file}") : copy($stub->getPathname(), "{$path}/{$file}");
                if(!$created) {
                    $out->writeln("<error>failed:  {$file}</error>");
                    return Command::FAILURE;
                }
                $out->writeln("<info>created: {$file}</info>");
            }

            chmod("{$path}/zubzet", 0755);
            chmod("{$path}/project.sh", 0755);

            // The caller's COMPOSER_VENDOR_DIR points at the framework's vendor, not the new project's.
            $composer = "env -u COMPOSER_VENDOR_DIR composer --no-interaction --working-dir=" . escapeshellarg($path);

            // Test only: resolve the framework from this checkout instead of Packagist.
            $framework = realpath(__DIR__ . "/../../..");
            $out->writeln("<info>composer config repositories.zubzet path {$framework}</info>");
            passthru("{$composer} config repositories.zubzet path " . escapeshellarg($framework), $exitCode);
            if($exitCode !== 0) {
                $out->writeln("<error>composer config failed</error>");
                return Command::FAILURE;
            }

            $out->writeln("<info>composer require " . self::PACKAGE . "</info>");
            passthru("{$composer} require " . escapeshellarg(self::PACKAGE), $exitCode);
            if($exitCode !== 0) {
                $out->writeln("<error>composer require failed</error>");
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        }

    }
