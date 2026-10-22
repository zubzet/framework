<?php

    namespace ZubZet\Tooling\Modifiers;

    class Folder extends BaseModifier {

        public function shouldExist(string|array $paths) {
            if(!is_array($paths)) {
                $paths = [$paths];
            }

            foreach($paths as $path) {
                if(is_dir($path)) {
                    $this->out->writeln("Folder <comment>$path</comment> already exists, continuing...");
                    continue;
                }

                $this->out->writeln("Creating folder <info>$path</info>");
                if($this->upgrade->dry) continue;

                if(!mkdir($path, 0755, true)) {
                    throw new \RuntimeException("Failed to create folder '$path'");
                }
            }
        }

        public function shouldNotExist(string|array $paths, bool $onlyIfEmpty = false) {
            if(!is_array($paths)) {
                $paths = [$paths];
            }

            foreach($paths as $path) {
                if(!is_dir($path)) {
                    $this->out->writeln("Folder <comment>$path</comment> already does not exist, continuing...");
                    continue;
                }

                if($onlyIfEmpty && !empty(glob("$path/*"))) {
                    $this->out->writeln("Folder <comment>$path</comment> is not empty, keeping it...");
                    continue;
                }

                $this->out->writeln("Removing empty folder <info>$path</info>");
                if($this->upgrade->dry) continue;

                if(!rmdir($path)) {
                    throw new \RuntimeException("Failed to remove folder '$path'");
                }
            }
        }

        public function shouldNotExistIfEmpty(string|array $paths) {
            if(!is_array($paths)) {
                $paths = [$paths];
            }

            // Remove paths that are not empty
            $paths = array_filter($paths, function($path) {
                $path = rtrim($path, DIRECTORY_SEPARATOR);
                return is_dir($path) && empty(glob("$path/*"));
            });

            $this->shouldNotExist($paths);
        }
    }

?>