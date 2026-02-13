<?php

    namespace ZubZet\Tooling\Version;

use DateTime;
use Symfony\Component\Console\Helper\QuestionHelper;
    use Symfony\Component\Console\Question\ConfirmationQuestion;
    use Symfony\Component\Finder\Finder;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\JsonModifier;
    use ZubZet\Tooling\Modifiers\MatchingModifier;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Version\BaseVersion;

    class V1_1_0 extends BaseVersion implements VersionInterface {

        public string $stability = ReleaseState::ReleaseCandidate;

        public function upgrade(): bool {

            // Add new settings for elevated database credentials
            $settings = new SettingsIni($this, "settings");
            $settings->addProperty("dbusername_elevated", "");
            $settings->addProperty("dbpassword_elevated", "");

            $settings->save();


            // Remove ZubZet Migrations from the App folder
            $migrations = new MatchingModifier($this, "app_zubzet_migrations");
            $migrations->from(["./app"]);

            $statements = [
                "z_email_verify",
                "z_file",
                "z_interaction_log",
                "z_interaction_log_category",
                "z_language",
                "z_logintoken",
                "z_logintry",
                "z_login_too_many_tries",
                "z_password_reset",
                "z_role",
                "z_role_permission",
                "z_uniqueref",
                "z_user",
                "z_user_role",
                "z_user_permission"
            ];

            foreach($statements as $statement) {
                $migrations->matchLineByLine(
                    '/CREATE\s+TABLE\s+`' . preg_quote($statement, '/') . '`/i',
                    "ZubZet Migrations: Remove $statement table creation. ZubZet is handling this internally now."
                );
            }

            if(count($migrations->getIssues()) > 0) {
                $files = array_values(array_unique(array_map(
                    fn($issue) => $issue->file,
                    $migrations->getIssues()
                )));

                $files = array_values(array_unique($files));

                foreach($files as $file) {
                    $this->upgrade->output->writeln("<error>Found ZubZet Migrations in file: $file</error>");
                }

                $helper = new QuestionHelper();
                $question = new ConfirmationQuestion(
                    'Do you want to delete the matched files? [Y/n]: ',
                    false
                );

                if($helper->ask($this->upgrade->input, $this->upgrade->output, $question)) {
                    foreach($files as $file) {
                        unlink($file);
                        $this->upgrade->output->writeln("<info>Deleted file: $file</info>");
                    }
                }
            }



            // Replace old Seed Command with new one
            $packageJson = new JsonModifier($this, "package-json-seed");
            $packageJson->from("package.json");
            $packageJson->modify(function(array $data): ?array {
                $seed = &$data["scripts"]["seed"];

                if(!isset($seed)) return null;
                if(str_contains($seed, "db:seed")) return null;

                if(str_contains($seed, "docker exec application")) {
                    $seed = "docker exec application ";
                }

                $seed .= "php zubzet db:seed";

                return $data;
            });


            // Update the docker-compose.yml - Add migration service
            $dockerCompose = new FileContent($this, "docker-compose-migration");
            $dockerCompose->find("docker-compose-base.yml");
            $dockerCompose->shouldChangeIfNotIncludes("migration:");
            $dockerCompose->automateChange(function($content) {
                // 1. Migration-Service robust unter dem 'services:' Key einfügen
                $migrationService = "\n  migration:\n"
                    . "    <<: *built-application-image\n"
                    . "    container_name: migration\n"
                    . "    command: php zubzet db:migrate --force\n"
                    . "    restart: \"no\"\n"
                    . "    healthcheck:\n"
                    . "      test: [\"CMD\", \"php\", \"zubzet\", \"db:status\"]\n"
                    . "      interval: 5s\n"
                    . "      timeout: 10s\n"
                    . "      retries: 5\n"
                    . "      start_period: 30s\n"
                    . "    depends_on:\n"
                    . "      database:\n"
                    . "        condition: service_healthy";

                $content = preg_replace('/^services:\s*$/m', "services:" . $migrationService, $content, 1);

                // 2. depends_on robust in der Blaupause 'x-built-application-image' anpassen
                $content = preg_replace_callback(
                    '/^x-built-application-image:.*?(?=\n^[a-zA-Z_-]+:|\z)/ms',
                    function ($matches) {
                        $block = $matches[0];
                        $dependency = "\n    migration:\n      condition: service_completed_successfully";

                        // Prüfen, ob 'depends_on:' in der Blaupause bereits existiert
                        if (preg_match('/^  depends_on:\s*$/m', $block)) {
                            // Falls ja, füge die Migration-Abhängigkeit als erstes Element unter depends_on ein
                            $block = preg_replace(
                                '/^  depends_on:\s*$/m',
                                "  depends_on:" . $dependency,
                                $block,
                                1
                            );
                        } else {
                            // Falls nein, erstelle den depends_on Block am Ende des Blocks neu
                            $block = rtrim($block) . "\n  depends_on:" . $dependency . "\n";
                        }

                        return $block;
                    },
                    $content,
                    1
                );

                return $content;
            });
            $dockerCompose->demandChange([
                "Add migration service to docker-compose-base.yml",
                "This ensures database migrations run before the application starts.",
            ]);


            // Check if any existing Migration doesnt follow the new naming convention and warn about it
            $sqlFinder = new Finder();
            $sqlFinder->in("./app/Database/migrations")
                ->files()
                ->name("*.sql");

            $errorFiles = [];
            // Expected: YYYY-MM-DD_Name
            foreach($sqlFinder as $file) {
                $sqlName = $file->getBasename(".sql");
                $segments = explode("_", $sqlName);

                if(count($segments) < 2) {
                    $errorFiles[] = [$file->getRelativePathname()];
                    continue;
                }

                $dateString = $segments[0];
                if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                    $errorFiles[] = [$file->getRelativePathname()];
                    continue;
                }

                $dateObj = DateTime::createFromFormat('Y-m-d', $dateString);
                if(!$dateObj) {
                    $errorFiles[] = [$file->getRelativePathname()];
                    continue;
                }

                $now = new DateTime('today');
                if($dateObj > $now) {
                    $errorFiles[] = [$file->getRelativePathname()];
                    continue;
                }

                $version = 0;
                $nameStartIndex = 1;

                if((int)$dateObj->format('Y') < 2000) {
                    $errorFiles[] = [$file->getRelativePathname()];
                    continue;
                }

                if(isset($segments[1]) && (filter_var($segments[1], FILTER_VALIDATE_INT))) {
                    $version = $segments[1];
                    $nameStartIndex = 2;
                }

                $nameParts = array_slice($segments, $nameStartIndex);
                $name = implode('_', $nameParts);
                if(empty($name)) {
                    $errorFiles[] = [$file->getRelativePathname()];
                    continue;
                }
            }

            $this->upgrade->output->writeln("<error>Found " . count($errorFiles) . " migration files with invalid names:</error>");
            foreach($errorFiles as $file) {
                $this->upgrade->output->writeln("<error>- {$file[0]}</error>");
            }

            $this->upgrade->output->writeln("<comment>Please ensure all migration files follow the naming convention 'YYYY-MM-DD_Name.sql' or 'YYYY-MM-DD_Version_Name.sql' and that the date is valid and not in the future.</comment>");

            return true;
        }
    }

?>