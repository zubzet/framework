<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Modifiers\Folder;
    use ZubZet\Tooling\Version\BaseVersion;
    use ZubZet\Tooling\Modifiers\RemoveFile;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\ComposerModifier;

    class V0_11_0 extends BaseVersion implements VersionInterface {
        public string $stability = ReleaseState::Stable;

        public function upgrade(): bool {
            // New mail security setting
            $settings = new SettingsIni($this, "mail-settings");
            $settings->addProperty("mail_security", "tls", "mail_smtp");
            $settings->save();

            $fileContent = new FileContent($this, "mail-docker");
            $fileContent->optionalIfNotFound();
            $fileContent->find("docker-compose-base.yml", "packaging");
            $fileContent->shouldChangeIfNotIncludes("CONFIG_MAIL_SECURITY");
            $fileContent->automateChange(function($fileContent) {
                $candidates = [
                    '/^([ \t]*)CONFIG_MAIL_PASSWORD:\s*.*\R/m',
                    '/^([ \t]*)CONFIG_MAIL_USER:\s*.*\R/m',
                    '/^([ \t]*)## Mailer[^\r\n]*\R/m',
                ];

                foreach($candidates as $pattern) {
                    $new = preg_replace(
                        $pattern,
                        "$0" . '$1' . "CONFIG_MAIL_SECURITY: \"false\"\n",
                        $fileContent,
                        1,
                        $count,
                    );
                    if($count) return $new;
                }

                return $fileContent;
            });
            $fileContent->demandChange("Set the environment variable CONFIG_MAIL_SECURITY to false");

            // ZubZet installation via composer
            $fileContent = new FileContent($this, "index-main");
            $fileContent->find("index.php");
            $fileContent->shouldChangeIfIncludes("main.php");
            $fileContent->automateChange(function($fileContent) {
                return preg_replace(
                    '~^[ \t]*require(?:_once)?[^\S\r\n]*\(?[^\S\r\n]*["\']z_framework/main\.php["\'][^\S\r\n]*\)?[^\S\r\n]*;[^\S\r\n]*(?:\R|$)~mi',
                    '',
                    $fileContent,
                );
            });
            $fileContent->demandChange([
                "Update the index.php and remove the line:",
                'require_once "z_framework/main.php";',
            ]);

            $fileContent = new FileContent($this, "index-autoload");
            $fileContent->find("index.php");
            $fileContent->shouldChangeIfNotIncludes("autoload.php");
            $fileContent->automateChange(function($fileContent) {
                // Remove any existing autoload require lines
                $fileContent = preg_replace(
                    '/^[ \t]*require(?:_once)?\s*\(?\s*[\'"]vendor\/autoload\.php[\'"]\s*\)?\s*;\s*\R?/mi',
                    '',
                    $fileContent,
                );

                // Insert an autoload line exactly after the chdir(...) line
                return preg_replace(
                    '/(^[ \t]*chdir\([^;]*\);\s*\R)/mi',
                    "$1    require_once \"vendor/autoload.php\";\n",
                    $fileContent,
                    1,
                );
            });
            $fileContent->demandChange([
                "Update the index.php and add the line before the framework is initialized:",
                'require_once "vendor/autoload.php";',
            ]);

            $composer = new ComposerModifier($this, "composer");
            $composer->upgradeToCurrentVersion();

            $fileContent = new FileContent($this, "composer-zubzet-install");
            $fileContent->find("composer.lock");
            $fileContent->shouldChangeIfNotIncludes("zubzet/framework");
            $fileContent->automateChangeCmd("composer install");
            $fileContent->demandChange("Install ZubZet framework via Composer");

            $fileContent = new FileContent($this, "submodule-zubzet");
            $fileContent->find(".gitmodules");
            $fileContent->optionalIfNotFound();
            $fileContent->shouldChangeIfIncludes("z_framework");
            $fileContent->shouldChangeIfIncludes("zubzet");
            $fileContent->automateChangeCmd("git rm z_framework");
            $fileContent->demandChange("Remove ZubZet framework as submodule");

            $folder = new Folder($this, "folder-submodule-zubzet");
            $folder->shouldNotExist(["z_framework"]);

            // Remove old framework files
            $zFramework = new RemoveFile($this, "old-file-cleanup");
            $zFramework->from([
                ".z_framework",
                "composer.phar",
                "cv.txt",
            ]);

            return true;
        }
    }

?>