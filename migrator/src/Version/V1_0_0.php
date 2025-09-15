<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Modifiers\Folder;
    use ZubZet\Tooling\Version\BaseVersion;
    use ZubZet\Tooling\Modifiers\RemoveFile;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\IncludedFile;
    use ZubZet\Tooling\Modifiers\JsonModifier;
    use ZubZet\Tooling\Modifiers\FolderContent;
    use ZubZet\Tooling\Modifiers\CommandModifier;
    use ZubZet\Tooling\Modifiers\ComposerModifier;
    use ZubZet\Tooling\Modifiers\MatchingModifier;

    class V1_0_0 extends BaseVersion implements VersionInterface {
        public string $stability = ReleaseState::ReleaseCandidate;

        public function upgrade(): bool {
            $composer = new ComposerModifier($this, "composer");
            $composer->upgradeToCurrentVersion();

            // Remove: Lite Mode
            $settings = new SettingsIni($this, "settings-lite-mode");
            $settings->removeProperty("lite_mode", [
                "Lite Mode has been removed.",
                "Make sure your application works without it.",
            ]);
            $settings->save();

            // Remove: The language system
            $settings = new SettingsIni($this, "settings-language");
            $settings->removeProperty("anonymous_available_languages");
            $settings->removeProperty("anonymous_language");
            $settings->save();

            // Remove: The sitemap feature
            $settings = new SettingsIni($this, "settings-sitemap");
            $settings->removeProperty("sitemapPublicDefault", [
                "The sitemap feature has been removed.",
                "You may remove all related attributes from your application.",
            ]);
            $settings->save();

            // Unify the configuration across installations
            $settings = new SettingsIni($this, "settings");
            $settings->removeProperty("dedicated_mail");

            $settings->addProperty("allow_env_config", "true");
            $settings->addProperty("execution_type", "test");
            $settings->addProperty("registerRoleIdSecondary", "", "registerRoleId");

            $settings->modifyProperty("uploadFolder", "webroot/uploads/");

            $settings->collapseConsecutiveEmptyRows();
            $settings->assertEmptyLastRow();
            $settings->save();

            // Allow for different vendor folder locations in entrypoint
            $fileContent = new FileContent($this, "index-vendor-env");
            $fileContent->find("index.php");
            $fileContent->shouldChangeIfNotIncludes("COMPOSER_VENDOR_DIR");
            $fileContent->automateChange(function($fileContent) {
                return preg_replace(
                    '/^([ \t]*)require(?:_once)?\s*\(?\s*([\'"])vendor\/autoload\.php\2\s*\)?\s*;\s*$/mi',
                    '$1$source = getenv("COMPOSER_VENDOR_DIR") ?: "./vendor";' . "\n"
                    . '$1require_once "$source/autoload.php";' . "\n",
                    $fileContent,
                    1,
                );
            });
            $fileContent->demandChange([
                "Update the index.php and replace the composer auto loading with:",
                '$source = getenv("COMPOSER_VENDOR_DIR") ?: "./vendor";',
                'require_once "$source/autoload.php";',
            ]);

            // Setup "php zubzet" command
            $namedEntryPoint = new IncludedFile($this, "zubzet-entrypoint");
            $namedEntryPoint->from("zubzet");
            $namedEntryPoint->to("./");

            $userCodeNonViews = [
                "z_controllers",
                "z_models",
                "app/Controllers",
                "app/Models",
            ];

            // Detect deprecated methods
            $methodMatcher = new MatchingModifier($this, "deprecated-methods");
            $methodMatcher->from($userCodeNonViews);

            $methods = [
                "getConfigFile",
                "updateErrorHandling",
                "getPreAction",
                "getActionStack",
                "getLastController",
                "getControllerStack",
                "showFile",
                "send",
                "getReCaptchaV3Score",
                "renderPDF",
            ];

            foreach($methods as $method) {
                $methodMatcher->matchLineByLine(
                    "~
                        \\??->                      # -> or ?->
                        (?:\\s|/\\*.*?\\*/)*        # optional spaces or block comments
                        (?:                         # method literal or quoted in braces
                            \\{\\s*(?:\"\\Q{$method}\\E\"|'\\Q{$method}\\E')\\s*\\}
                        | \\Q{$method}\\E
                        )
                        (?:\\s|/\\*.*?\\*/)*        # optional spaces or inline /*...*/ before (
                        \\(
                    ~ix",
                    "The {$method} method has been removed.",
                );
            }
            $methodMatcher->warn();

            // Detect deprecated translations
            $translations = new MatchingModifier($this, "deprecated-translations");
            $translations->from(array_merge($userCodeNonViews, ["z_views", "app/Views"]));
            $translationWarning = "The translation system has been removed. Please remove it from your project to upgrade.";
            $translations->matchLineByLine(
                '~\$\s*opt\s*\[\s*(["\'])lang\1\s*\]\s*\(\s*(["\'])[A-Za-z0-9_.-]+\2\s*\)\s*;?~',
                $translationWarning,
            );
            $translations->matchLineByLine(
                '~\}\s*,\s*(["\'])lang\1\s*=>~',
                $translationWarning,
            );
            $translations->matchLineByLine(
                '~\bz_lang\b~',
                $translationWarning,
            );
            $translations->warn();

            // Detect deprecated password handler
            $pwHandler = new MatchingModifier($this, "deprecated-password-handler");
            $pwHandler->from($userCodeNonViews);
            $pwHandler->matchLineByLine(
                    "/\bpasswordHandler\b/i",
                    [
                        "The passwordHandler has been removed, it is now available as a package.",
                        "More info about the implementation at:",
                        "https://github.com/zubzet/password-hash-utilities",
                        "",
                        "Also see the refactor for reference:",
                        "https://github.com/zubzet/framework/commit/68d1cdeae7e83e4530c0d617cbe9e5f04104304e"
                    ],
                );
            $pwHandler->warn();

            // Migrate the userspace folder to app/
            $folder = new Folder($this, "folders-userspace");
            $folder->shouldExist([
                "app/Controllers",
                "app/Database",
                "app/Models",
                "app/Routes",
                "app/Views",
            ]);

            $folderContent = new FolderContent($this, "migration-userspace");
            $folderContent->move("z_controllers", "app/Controllers");
            $folderContent->move("z_models", "app/Models");
            $folderContent->move("z_views", "app/Views");
            $folderContent->move("z_database", "app/Database");

            $example = new IncludedFile($this, "example-router");
            $example->from("ExampleRouter.php");
            $example->to("app/Routes");

            $folder = new Folder($this, "folders-old-userspace");
            $folder->shouldNotExist([
                "z_controllers",
                "z_models",
                "z_views",
                "z_database",
            ]);

            // Setup userspace auto loading
            $autoload = new JsonModifier($this, "userspace-autoload");
            $autoload->from("composer.json");
            $autoload->modify(function(array $data): ?array {
                if(!isset($data["autoload"])) {
                    $data["autoload"] = [];
                }

                if(!isset($data["autoload"]["psr-4"])) {
                    $data["autoload"]["psr-4"] = [];
                }

                $data["autoload"]["psr-4"]["App\\"] = "app/";
                return $data;
            });
            $autoload->ifModified(function() {
                $classMap = new CommandModifier($this, "composer-dump-autoload");
                $classMap->runCommand("composer dump-autoload -o");
            });

            // Setup webroot
            $folder = new Folder($this, "webroot-folder");
            $folder->shouldExist(["webroot"]);

            // Setup .htaccess
            $htaccess = new IncludedFile($this, "webroot-htaccess");
            $htaccess->from(".htaccess");
            $htaccess->to("webroot");

            // Remove old .htaccess
            $htaccess = new RemoveFile($this, "remove-old-htaccess");
            $htaccess->from(".htaccess");

            // Setup new entrypoint
            $entryPoint = new IncludedFile($this, "webroot-entrypoint");
            $entryPoint->from("index.php");
            $entryPoint->to("webroot");

            // Move assets etc. to webroot
            $folderContent = new FolderContent($this, "migration-userspace");
            $folderContent->moveWithParentFolder("assets", "webroot/assets");
            $notice = "Only the assets folder was moved. ";
            $notice .= "If you have other public files, ";
            $notice .= "please move them to the webroot folder manually.";
            $this->upgrade->output->writeln($notice);

            // Migrate the uploads to webroot
            $folder = new Folder($this, "folder-upload");
            $folder->shouldExist(["webroot/uploads"]);

            $folderContent = new FolderContent($this, "migration-uploads");
            $folderContent->move("uploads", "webroot/uploads");

            $folder = new Folder($this, "folder-old-upload");
            $folder->shouldNotExist(["uploads"]);
            $folder->shouldNotExistIfEmpty(["webroot/uploads"]);

            // Update the package json database import
            $packageJson = new JsonModifier($this, "package-json-seed");
            $packageJson->optional();
            $packageJson->from("package.json");
            $packageJson->modify(function(array $data): ?array {
                $seed = &$data["scripts"]["seed"];

                if(!isset($seed)) return null;
                if(!str_contains($seed, "import.php")) return null;

                $seed = "docker exec application php app/Database/import.php";

                return $data;
            });

            // Update the package json to use docker only
            $packageJson = new JsonModifier($this, "package-json-start");
            $packageJson->optional();
            $packageJson->from("package.json");
            $packageJson->modify(function(array $data): ?array {
                $start = &$data["scripts"]["start"];

                if(!isset($start)) return null;
                if(!str_contains($start, "npm run docker-compose -- up")) return null;

                $start = implode(" && ", [
                    "npm install",
                    "npm run docker-compose -- up --remove-orphans --build -d",
                    "docker exec application composer install",
                    "npm run seed",
                ]);

                return $data;
            });

            // Replace database import script if exists
            $import = new IncludedFile($this, "database-import");
            $import->optionalIfNotFound();
            $import->from("import.php");
            $import->to("app/Database");

            return true;
        }
    }

?>