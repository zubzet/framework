<?php
    namespace ZubZet\Framework\Support\Commands;

    use Composer\InstalledVersions;
    use RecursiveIteratorIterator;
    use RecursiveDirectoryIterator;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    final class Install extends Command {

        protected function configure(): void {
            $this->setName("install");
            $this->setDescription("Builds a new ZubZet project.");

            $this->addArgument("path", InputArgument::REQUIRED, "The project folder to build the project in.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $path = (string) $in->getArgument("path");

            // Project folder: must exist and be writable
            $project = realpath($path);
            if($project === false || !is_dir($project)) {
                $out->writeln("<error>Folder does not exist: {$path}</error>");
                return Command::FAILURE;
            }

            if(!is_writable($project)) {
                $out->writeln("<error>Folder is not writable: {$project}</error>");
                return Command::FAILURE;
            }

            // Project template: resolved through Composer, so it does not depend on where this file lives
            $template = realpath(InstalledVersions::getInstallPath("zubzet/framework") . "/stubs/project");
            if($template === false) {
                $out->writeln("<error>Project template not found in the zubzet/framework package</error>");
                return Command::FAILURE;
            }

            // Copy the template, SELF_FIRST: folders come before their contents, so they exist when the files are copied
            $entries = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($template, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
            );

            foreach($entries as $entry) {
                $relativePath = substr($entry->getPathname(), strlen($template) + 1);
                $target = "{$project}/{$relativePath}";

                // Never overwrite, files already in the project stay untouched
                if(file_exists($target)) {
                    $out->writeln("exists:  {$relativePath}");
                    continue;
                }

                $created = $entry->isDir() ? mkdir($target) : copy($entry->getPathname(), $target);
                if(!$created) {
                    $out->writeln("<error>failed:  {$relativePath}</error>");
                    return Command::FAILURE;
                }

                $out->writeln("<info>created: {$relativePath}</info>");
            }

            // copy() does not keep the executable bit
            foreach(["zubzet", "project.sh"] as $script) {
                if(chmod("{$project}/{$script}", 0755)) continue;

                $out->writeln("<error>Could not make {$script} executable</error>");
                return Command::FAILURE;
            }

            // Require the framework, the caller's COMPOSER_VENDOR_DIR points at the framework's vendor, not the new project's
            $out->writeln("<info>composer require zubzet/framework</info>");
            passthru("env -u COMPOSER_VENDOR_DIR composer require zubzet/framework --no-interaction --working-dir=" . escapeshellarg($project), $exitCode);

            // 127: env could not find the composer binary
            if($exitCode === 127) {
                $out->writeln("<error>Composer is required but was not found in your PATH.</error>");
                return Command::FAILURE;
            }

            if($exitCode !== 0) {
                $out->writeln("<error>composer require failed, run it again inside {$project}</error>");
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        }

    }
