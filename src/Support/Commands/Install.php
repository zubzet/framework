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

                // Never overwrite: composer.json, the lock file and vendor are already there, user files stay untouched
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

            // A project without an encryption key gets its own, an existing key is never replaced
            $settingsFile = "{$project}/z_config/z_settings.ini";
            $settings = parse_ini_file($settingsFile) ?: [];
            if(empty($settings["encryption_key"])) {
                $key = bin2hex(random_bytes(32));

                // Matches the encryption_key line, whatever its value
                $keyLine = '/^encryption_key\s*=.*$/m';

                $content = file_get_contents($settingsFile);
                $content = preg_match($keyLine, $content)
                    ? preg_replace($keyLine, "encryption_key = {$key}", $content)
                    : rtrim($content) . "\nencryption_key = {$key}\n";

                if(false === file_put_contents($settingsFile, $content)) {
                    $out->writeln("<error>Could not write the encryption key to z_config/z_settings.ini</error>");
                    return Command::FAILURE;
                }

                $out->writeln("<info>generated: encryption_key in z_config/z_settings.ini</info>");
            }

            // copy() does not keep the executable bit
            foreach(["zubzet", "project"] as $script) {
                if(chmod("{$project}/{$script}", 0755)) continue;

                $out->writeln("<error>Could not make {$script} executable</error>");
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        }

    }
