<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\Modifiers\ComposerModifier;
    use ZubZet\Tooling\Modifiers\FileContent;
    use ZubZet\Tooling\Modifiers\RemoveFile;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\Modifiers\ViewMigration;
    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Version\BaseVersion;

    class V1_3_0 extends BaseVersion implements VersionInterface {

        public string $stability = ReleaseState::Alpha;

        public function upgrade(): bool {
            // ZubZet 1.3.0 replaces the return-type view renderer with the Katana
            // Blade engine. Convert every legacy view and layout to .blade.php.
            $views = new ViewMigration($this, "blade-view-migration");
            $views->from(["./app/Views"]);
            $views->migrate();

            // Add the health endpoint toggle to ini settings
            $healthEndpoint = new SettingsIni($this, "health-endpoint-settings");
            $healthEndpoint->addProperty("health_endpoint_enabled", "true");
            $healthEndpoint->save();

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

            // Upgrade composer dependencies (pulls in the Katana-backed framework).
            $composer = new ComposerModifier($this, "composer");
            $composer->upgradeToCurrentVersion();

            return true;
        }
    }

?>
