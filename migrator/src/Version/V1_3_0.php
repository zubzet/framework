<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\Modifiers\ComposerModifier;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\JsonModifier;
    use ZubZet\Tooling\Modifiers\RemoveFile;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\Modifiers\ViewMigration;
    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Version\BaseVersion;

    class V1_3_0 extends BaseVersion implements VersionInterface {

        public string $stability = ReleaseState::Alpha;

        public function upgrade(): bool {
            // The 1.3.0 migrations need MariaDB 11.7 or newer
            // Matches "mariadb:" followed by a version tag starting with 5, 10 or 11.0 to 11.6, including its digest
            $outdatedMariaDb = '/mariadb:(?:5|10|11\.[0-6])\b[\w.\-@:]*/';

            $database = new FileContent($this, "mariadb-docker");
            $database->optionalIfNotFound();
            $database->find("docker-compose-base.yml", "packaging");
            $database->shouldChangeIfPattern($outdatedMariaDb);
            $database->automateChange(function(string $content) use ($outdatedMariaDb): string {
                return preg_replace($outdatedMariaDb, "mariadb:11.8", $content);
            });
            $database->demandChange([
                "ZubZet 1.3.0 requires MariaDB 11.7 or newer.",
                "Upgrade the database server before applying the migrations of this release.",
                "",
                "Documentation: https://zubzet.com/docs/v1.3.0/setup/upgrade/1.2.0-to-1.3.0/",
            ]);

            // ZubZet 1.3.0 replaces the return-type view renderer with the Katana
            // Blade engine. Convert every legacy view and layout to .blade.php.
            $views = new ViewMigration($this, "blade-view-migration");
            $views->from(["./app/Views"]);
            $views->migrate();

            // Add the health endpoint toggle to ini settings
            $healthEndpoint = new SettingsIni($this, "health-endpoint-settings");
            $healthEndpoint->addProperty("health_endpoint_enabled", "true");
            $healthEndpoint->save();

            // Environment variables only override existing settings, so CONFIG_ENCRYPTION_KEY needs the empty line
            $encryptionKey = new SettingsIni($this, "encryption-key-settings");
            $encryptionKey->addProperty("encryption_key", "");
            $encryptionKey->save();

            // Every POST needs the CSRF token now, a raw HTML form can only carry it as hidden _csrf field
            $addCsrfField = function(string $content, string $field): string {
                // Forms do not nest, so each part up to the next </form> holds at most one form
                $parts = preg_split('/(<\/form>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

                foreach($parts as $i => $part) {
                    // Only post forms need the token
                    $isPostForm = preg_match('/<form\b[^>]*\bmethod\s*=\s*["\']?post\b[^>]*>/i', $part, $openingTag);
                    if(!$isPostForm) continue;

                    // Already carries the token
                    if(str_contains($part, "CSRF::field") || str_contains($part, "_csrf")) continue;

                    // Split the part right behind the opening tag
                    $tagEnd = strpos($part, $openingTag[0]) + strlen($openingTag[0]);
                    $beforeField = substr($part, 0, $tagEnd);
                    $afterField = substr($part, $tagEnd);

                    // The tag shares its line with more content, so the field goes right behind it
                    $hasOwnLine = preg_match('/^(\R)([ \t]*)/', $afterField, $nextLine);
                    if(!$hasOwnLine) {
                        $parts[$i] = $beforeField . $field . $afterField;
                        continue;
                    }

                    // Otherwise the field gets its own line, indented like the line below the tag
                    $lineBreak = $nextLine[1];
                    $indent = $nextLine[2];
                    $parts[$i] = $beforeField . $lineBreak . $indent . $field . $afterField;
                }

                return implode("", $parts);
            };

            $views = "./app/Views";
            if(is_dir($views)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($views, \FilesystemIterator::SKIP_DOTS),
                );

                foreach($files as $file) {
                    if("php" !== $file->getExtension()) continue;

                    // Blade gets its own syntax, plain PHP views the short echo tag
                    $field = '<?= \ZubZet\Framework\Security\CSRF::field() ?>';
                    if(str_ends_with($file->getFilename(), ".blade.php")) {
                        $field = '{!! \ZubZet\Framework\Security\CSRF::field() !!}';
                    }

                    // Nothing would change, so the view has no post form that lacks the token
                    $content = file_get_contents($file->getPathname());
                    if($addCsrfField($content, $field) === $content) continue;

                    // The relative path keeps the step name unique for files of the same name
                    $relativePath = substr($file->getPathname(), strlen($views) + 1);

                    $form = new FileContent($this, "csrf-field-$relativePath");
                    $form->find($file->getFilename(), $file->getPath());
                    $form->shouldChangeIfPattern('/<form\b/i');
                    $form->automateChange(function(string $content) use ($addCsrfField, $field): string {
                        return $addCsrfField($content, $field);
                    });

                    $form->demandChange([
                        "ZubZet 1.3.0 checks a CSRF token on every POST request.",
                        "A raw HTML form with method=\"post\" cannot send the X-CSRF-Token header and has to carry the token as hidden _csrf field.",
                        "",
                        "Documentation: https://zubzet.com/docs/v1.3.0/core-features/csrf-protection/",
                    ]);
                }
            }

            // App-local copies of Z.js predate the CSRF header, the framework serves the current one itself
            $zJsCopies = new RemoveFile($this, "remove-app-z-js");
            $zJsCopies->from([
                "./webroot/assets/js/Z.js",
                "./assets/js/Z.js",
            ]);

            // PHP 8.0 stays on otphp 10, which composer refuses to install because of two advisories
            $auditIgnore = new JsonModifier($this, "composer-audit-ignore-otphp");
            $auditIgnore->from("composer.json");
            $auditIgnore->modify(function(array $data): ?array {
                $ignore = $data["config"]["audit"]["ignore"] ?? [];

                // The list form ignores an advisory everywhere, the map form needs the same meaning per id
                if(array_values($ignore) === $ignore) {
                    $ignore = array_fill_keys($ignore, "Ignored before the ZubZet 1.3.0 upgrade");
                }

                foreach(["GHSA-2jx3-65f3-xr8r", "GHSA-g7m4-839x-ch6v"] as $advisory) {
                    $ignore[$advisory] ??= [
                        "apply" => "block",
                        "reason" => "otphp 11 requires PHP 8.1+, PHP 8.0 stays on 10.x",
                    ];
                }

                $data["config"]["audit"]["ignore"] = $ignore;
                return $data;
            });

            // Upgrade composer dependencies (pulls in the Katana-backed framework).
            $composer = new ComposerModifier($this, "composer");
            $composer->upgradeToCurrentVersion();

            return true;
        }
    }

?>
