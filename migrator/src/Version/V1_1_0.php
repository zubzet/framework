<?php

    namespace ZubZet\Tooling\Version;

    use Symfony\Component\Finder\Finder;
    use ZubZet\Tooling\AutomatedChanges\V1_1_0\MigrationDockerServiceChange;
    use ZubZet\Tooling\AutomatedChanges\V1_1_0\MigrationNamingConventionChange;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\JsonModifier;
    use ZubZet\Tooling\Modifiers\MatchingModifier;
    use ZubZet\Tooling\Modifiers\RemoveFile;
    use ZubZet\Tooling\Modifiers\RenameModifier;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Version\BaseVersion;

    class V1_1_0 extends BaseVersion implements VersionInterface {

        public string $stability = ReleaseState::ReleaseCandidate;

        public function upgrade(): bool {

            // changePassword deprecation warning
            $changePassword = new MatchingModifier($this, "change-password-deprecation");
            $changePassword->from(["./app"]);
            $changePassword->matchLineByLine(
                '/z_login.*->updatePassword/',
                "Deprecation Warning: The changePassword function is deprecated and is replaced with the Authentication System. Please update your code accordingly."
            );
            $changePassword->warn();

            // language system deprecation warning
            $languageSystem = new MatchingModifier($this, "language-system-deprecation");
            $languageSystem->from(["./app"]);
            $languageSystem->matchLineByLine(
                '/\<\?php \}, "lang" => \[/',
                "Deprecation Warning: The language system is being deprecated. Please update your code accordingly."
            );
            $languageSystem->warn();

            $languageSystem->from(["./app"]);
            $languageSystem->matchLineByLine(
                '/z_language/',
                "Deprecation Warning: The language system is being deprecated. Please update your code accordingly."
            );
            $languageSystem->warn();


            // Add new settings for elevated database credentials
            $settings = new SettingsIni($this, "settings");
            $settings->addProperty("dbusername_elevated", "", "dbpassword");
            $settings->addProperty("dbpassword_elevated", "", "dbusername_elevated");
            $settings->save();

            // Remove ZubZet Migrations from the App folder
            $migrations = new MatchingModifier($this, "app-zubzet-migrations");
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
                "z_user_permission",
            ];

            foreach($statements as $statement) {
                $migrations->matchLineByLine(
                    '/CREATE\s+TABLE\s+`' . preg_quote($statement, '/') . '`/i',
                    "ZubZet Migrations: Remove $statement table creation. ZubZet is handling this internally now."
                );
            }

            // Find unique files with issues
            $files = array_values(array_unique(array_map(
                fn($issue) => $issue->file,
                $migrations->getIssues(),
            )));

            foreach($files as $file) {
                (new RemoveFile($this, "remove-old-zubzet-migration"))->from($file);
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

            // Removing Import.php script
            $importFile = new RemoveFile($this, "remove-import-script");
            $importFile->from("./app/Database/import.php");


            // Check if any existing Migration doesn't follow the new naming convention and warn about it
            $sqlFinder = new Finder();
            $sqlFinder->in("./app/Database/migrations")
                ->files()
                ->name("*.sql");

            $migrationNamingChange = new MigrationNamingConventionChange();

            $errorFiles = [];
            // Expected: YYYY-MM-DD_Name
            foreach($sqlFinder as $file) {
                $sqlName = $file->getBasename(".sql");
                if(!$migrationNamingChange->validateMigrationName($sqlName)) {
                    $errorFiles[] = $file->getPathname();
                }
            }

            $renameModifier = new RenameModifier($this, "rename-migrations");
            $renameModifier->from($errorFiles, [
                "Found migration files with invalid names.",
                "Please ensure all migration files follow the naming convention 'YYYY-MM-DD_Name.sql' or 'YYYY-MM-DD_Version_Name.sql' and that the date is valid and not in the future.",
            ], function($file) use ($migrationNamingChange) {
                // this is ai slop to at least try to give an automated change suggestion
                // The error mode is simply not having an automated change as the format
                // will be evaluated before suggesting it
                $suggestionName = $migrationNamingChange->renameAutomateChange($file);
                if(is_null($suggestionName)) return null;

                $filename = pathinfo($suggestionName, PATHINFO_FILENAME);
                if(!$migrationNamingChange->validateMigrationName($filename)) return null;

                return $suggestionName;
            });
            return true;
        }
    }

?>