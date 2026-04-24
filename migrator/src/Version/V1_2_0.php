<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\Modifiers\ComposerModifier;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\MatchingModifier;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Version\BaseVersion;

    class V1_2_0 extends BaseVersion implements VersionInterface {

        public string $stability = ReleaseState::ReleaseCandidate;

        public function upgrade(): bool {
            //
            // All deprecated logging functions and tables
            //
            $loggingDeprecations = [
                "logActionByCategory",
                "logAction",
                "getLogCategoryIdByName",
                "z_interaction_log",
                "z_interaction_log_category"
            ];

            $loggingDeprecation = new MatchingModifier($this, "logging-deprecation");
            $loggingDeprecation->from(["./app"]);
            foreach($loggingDeprecations as $loggingDeprecationItem) {
                $loggingDeprecation->matchLineByLine(
                    "/$loggingDeprecationItem/",
                    [
                        "ZubZet 1.2.0 introduces a new logging system that replaces the deprecated one.",
                        "Please migrate away from the legacy logging functions and tables to the new logging system.",
                        "",
                        "Documentation for the new logging system:",
                        "https://zubzet.com/docs/v1.2.0/core-features/logging/"
                    ]
                );

                $loggingDeprecation->warn();
            }


            // Add new settings for logging system
            $settings = new SettingsIni($this, "logger-settings");
            $settings->addProperty("logger_enabled", "true", "");
            $settings->addProperty("logger_type", "stream", "logger_enabled");
            $settings->addProperty("logger_stream_url", "z_config/app.log", "logger_type");
            $settings->addProperty("logger_level", "info", "logger_stream_url");
            $settings->save();

            //
            // PHP Superglobals → request()->input replacement
            //
            $superglobals = ["COOKIE", "POST", "GET", "REQUEST", "FILES", "SERVER"];

            $superglobalMatcher = new MatchingModifier($this, "superglobals-detection");
            $superglobalMatcher->from(["./app"]);

            foreach($superglobals as $global) {
                $superglobalMatcher->matchLineByLine(
                    '/\$_' . $global . '\[/',
                    "ZubZet 1.2.0 deprecates direct PHP superglobal access.",
                );
            }

            $affectedFiles = array_values(array_unique(array_map(
                fn($issue) => $issue->file,
                $superglobalMatcher->getIssues(),
            )));

            foreach($affectedFiles as $file) {
                $fileContent = new FileContent($this, "superglobals-" . basename($file));
                $fileContent->find(basename($file), dirname($file));
                $fileContent->shouldChangeIfPattern('/\$_(COOKIE|POST|GET|REQUEST|FILES|SERVER)\[/');
                $fileContent->automateChange(function(string $content): string {
                    return preg_replace(
                        '/\$_(COOKIE|POST|GET|REQUEST|FILES|SERVER)\[/',
                        'request()->input->$1[',
                        $content
                    );
                });
                $fileContent->demandChange([
                    "ZubZet 1.2.0 deprecates direct PHP superglobal access (\$_COOKIE, \$_POST, \$_GET, ...).",
                    "Please use request()->input->COOKIE / POST / GET / ... instead.",
                    "",
                    "Documentation: https://zubzet.com/docs/v1.2.0/core-features/request/",
                ]);
            }

            //
            // file_get_contents('php://input') → request()->input->body replacement
            //
            $phpInputMatcher = new MatchingModifier($this, "php-input-detection");
            $phpInputMatcher->from(["./app"]);
            $phpInputMatcher->matchLineByLine(
                '/file_get_contents\s*\(\s*[\'"]php:\/\/input[\'"]\s*\)/',
                "ZubZet 1.2.0 deprecates direct php://input access.",
            );

            $phpInputFiles = array_values(array_unique(array_map(
                fn($issue) => $issue->file,
                $phpInputMatcher->getIssues(),
            )));

            foreach($phpInputFiles as $file) {
                $fileContent = new FileContent($this, "php-input-" . basename($file));
                $fileContent->find(basename($file), dirname($file));
                $fileContent->shouldChangeIfPattern('/file_get_contents\s*\(\s*[\'"]php:\/\/input[\'"]\s*\)/');
                $fileContent->automateChange(function(string $content): string {
                    return preg_replace(
                        '/file_get_contents\s*\(\s*[\'"]php:\/\/input[\'"]\s*\)/',
                        'request()->input->body',
                        $content
                    );
                });
                $fileContent->demandChange([
                    "ZubZet 1.2.0 deprecates file_get_contents('php://input').",
                    "Please use request()->input->body instead.",
                    "",
                    "Documentation: https://zubzet.com/docs/v1.2.0/core-features/request/",
                ]);
            }

            $composer = new ComposerModifier($this, "composer");
            $composer->upgradeToCurrentVersion();



            //
            // Add logging folder to .gitignore
            //
            $gitignoreLogging = new FileContent($this, "gitignore-logs");
            $gitignoreLogging->find(".gitignore");
            $gitignoreLogging->shouldChangeIfNotIncludes("z_config/app.log");
            $gitignoreLogging->automateChange(function(string $content): string {
                return rtrim($content, "\n") . "\n/z_config/app.log\n";
            });
            $gitignoreLogging->demandChange([
                "ZubZet 1.2.0 writes logs to /z_config/app.log by default.",
                "Please add the file to .gitignore.",
            ]);


            //
            // Add SlowQuery and SlowRequest to ini settings
            //
            $slowSetting = new SettingsIni($this, "slow-settings");
            $slowSetting->addProperty("logger_slow_request_ms", "700");
            $slowSetting->addProperty("logger_slow_query_ms", "300", "slow_query_threshold");
            $slowSetting->save();


            //
            // Add DebugBar Hide Internal Queries to ini settings
            //
            $hideInternalQueries = new SettingsIni($this, "debugbar-settings");
            $hideInternalQueries->addProperty("debugbar_hide_internal_queries", "true");
            $hideInternalQueries->save();


            //
            // Add Maintenance Mode to ini settings
            //
            $maintenanceMode = new SettingsIni($this, "maintenance-mode-settings");
            $maintenanceMode->addProperty("maintenance_mode", "disabled");
            $maintenanceMode->save();


            return true;
        }
    }

?>