<?php

    namespace ZubZet\Tooling\AutomatedChanges\V1_1_0;

    class MigrationDockerServiceChange {

        public function extendWithMigrationService($content) {
            // 1. Migration-Service robust unter dem 'services:' Key einfügen
            $migrationService = <<<YAML
                                      migration:
                                        <<: *built-application-image
                                        command: php zubzet db:migrate
                                        restart: "no"
                                        depends_on:
                                          database:
                                            condition: service_healthy
                                    YAML;

            $content = preg_replace('/^services:\s*$/m', "services:\n" . $migrationService, $content, 1);

            // 2. depends_on robust in der Blaupause 'x-built-application-image' anpassen
            $content = preg_replace_callback(
                '/^x-built-application-image:.*?(?=\n^[a-zA-Z_-]+:|\z)/ms',
                function ($matches) {
                    $block = $matches[0];
                    $dependency = <<<YAML
                                        migration:
                                          condition: service_completed_successfully
                                    YAML;

                    // Check if 'depends_on:' already exists in the block
                    if (preg_match('/^  depends_on:\s*$/m', $block)) {
                        // Falls ja, füge die Migration-Abhängigkeit als erstes Element unter depends_on ein
                        $block = preg_replace(
                            '/^  depends_on:\s*$/m',
                            "  depends_on:\n" . $dependency,
                            $block,
                            1
                        );
                    } else {
                        // If not existing, create the depends_on block at the end of the block
                        $block = rtrim($block) . "\n  depends_on:" . $dependency . "\n";
                    }

                    return $block;
                },
                $content,
                1
            );

            return $content;
        }

    }