<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\Modifiers\Folder;
    use ZubZet\Tooling\Version\BaseVersion;
    use ZubZet\Tooling\Modifiers\RemoveFile;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\Modifiers\IncludedFile;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\JsonModifier;
    use ZubZet\Tooling\Modifiers\FolderContent;

    class V1_0_0 extends BaseVersion implements VersionInterface {
        public function upgrade(): bool {
            // TODO: Replace with a composer modifier
            $fileContent = new FileContent($this, "composer-zubzet");
            $fileContent->find("composer.json");
            $fileContent->shouldChangeIfNotIncludes('"zubzet/framework": "dev-main"');
            $fileContent->automateChangeCmd('composer require "zubzet/framework:^1.0" --with-all-dependencies');
            $fileContent->demandChange("Upgrade the ZubZet package version");

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
            // TODO: Show non assets folder that might need to be moved

            // Migrate the uploads to webroot
            $folder = new Folder($this, "folder-upload");
            $folder->shouldExist(["webroot/uploads"]);

            $folderContent = new FolderContent($this, "migration-uploads");
            $folderContent->move("uploads", "webroot/uploads");

            $folder = new Folder($this, "folder-old-upload");
            $folder->shouldNotExist(["uploads"]);
            $folder->shouldNotExistIfEmpty(["webroot/uploads"]);

            // Update the package json
            $packageJson = new JsonModifier($this, "app-folder-package-json");
            $packageJson->optional();
            $packageJson->from("package.json");
            $packageJson->modify(function(array $data): ?array {
                $seed = &$data["scripts"]["seed"];

                if(!isset($seed)) return null;
                if(!str_contains($seed, "z_database/seed.php")) return null;

                $seed = "php app/Database/import.php";

                return $data;
            });

            // TODO: Update $z_framework->handleRequest (Or fix in RC2)

            return true;
        }
    }

?>