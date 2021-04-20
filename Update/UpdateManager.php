<?php

    class UpdateManager {
        private $booter;
        public function __construct(&$booter) {
            $this->booter = $booter;
        }

        public function migrate_cli() {
            $target = __DIR__."/../../zubzet";
            if(file_exists($target)) {
                Console::write("Already done. Nothing to do!");
            } else {
                copy(__DIR__."/zubzet", $target);
            }
            Console::write("Get started by running {{light_blue}}php zubzet help");
        }

        public function run() {
            if(file_exists(".z_framework")) {
                $version = file_get_contents(".z_framework");
                Console::write("The legacy update has not been done yet. Starting...");
                if($version < 41) {
                    Console::write("Your installation will be updated to 0.9.0-edge ...");
                    include( __DIR__."/legacy_updater.php");
                } else {
                    Console::write("The legacy update was already finished. Cleaning up ...");
                    if(file_exists(".z_framework")) unlink(".z_framework");
                    if(file_exists("composer.phar")) unlink("composer.phar");
                }
                file_put_contents(".zVersion", "v0.9.0");
            }

            $currentVersion = file_get_contents(
                $this->booter->workingDir.".zVersion"
            );

            Console::write("Your codebase version is:\t  v", !Console::NEW_LINE);
            Console::write($currentVersion);

            Console::write("Your installed ZubZet version is: v", !Console::NEW_LINE);
            Console::write($this->booter->version);

            $updates = [];
            foreach(scandir(__DIR__."/updates") as $file) {
                if(in_array($file, ['.', '..'])) continue;
                $version = str_replace("Update_v", "", $file);
                $version = str_replace(".php", "", $version);
                $version = str_replace("_", ".", $version);
                $version = $this->fixedVersionLength($version);
                $updates[$version] = $file;
            }

            ksort($updates);
            require_once __DIR__."/Update.php";

            foreach($updates as $version => $update) {
                $readableVersion = str_replace("Update_", "", $update);
                $readableVersion = str_replace(".php", "", $readableVersion);
                $readableVersion = str_replace("_", ".", $readableVersion);

                require_once __DIR__."/updates/$update";
                $update = str_replace(".php", "", $update);
                if(version_compare($version, $this->fixedVersionLength($currentVersion), ">")) {
                    Console::write("\n>> Running update: $readableVersion...");
                    $update = new $update($this->booter);
                    $update->run(
                        $this->booter->req, 
                        $this->booter->res
                    );
                    Console::write("done.");
                }
            }

            file_put_contents(
                $this->booter->workingDir.".zVersion", 
                $this->booter->version
            );
        }

        public function fixedVersionLength($version, $versionLength = 4) {
            $versionLength = $versionLength - substr_count($version, ".");
            for($i = 1; $i < $versionLength; $i++) {
                $version .= ".0";
            }
            return $version;
        }

    }

?>