<?php

    namespace ZubZet\Tooling\Modifiers;

    class IncludedFile extends BaseModifier {

        private bool $isOptional = false;
        public function optionalIfNotFound(): void {
            $this->isOptional = true;
        }

        private ?string $fromFile = null;
        public function from(string $file) {
            $this->fromFile = $this->version->upgrade->rootDir . DIRECTORY_SEPARATOR;
            $this->fromFile .= "files" . DIRECTORY_SEPARATOR;
            $this->fromFile .= $this->version->semanticVersion . DIRECTORY_SEPARATOR;
            $this->fromFile .= $file;

            if(!is_file($this->fromFile)) {
                throw new \RuntimeException("The source file '{$this->fromFile}' does not exist.");
            }
        }

        public function to(string $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR);

            if(!is_dir($path)) {
                if($this->isOptional || $this->upgrade->dry) {
                    $this->out->writeln("The target folder <comment>$path</comment> does not exist. Skipping...");
                    return;
                }
                throw new \RuntimeException("The target folder '$path' does not exist.");
            }

            $path .= DIRECTORY_SEPARATOR . basename($this->fromFile);
            if(is_file($path)) {
                if(sha1_file($path) === sha1_file($this->fromFile)) {
                    $this->out->writeln("The target file <comment>$path</comment> already exists, skipping.");
                    return;
                }

                $this->out->writeln("The target file <info>$path</info> already exists, but the contents are different. It will be replaced.");
                if(!$this->confirmAutomatedChange()) $this->abortRequiringUserAction();
            }

            if($this->isOptional && !is_file($path)) {
                $this->out->writeln("The target file <comment>$path</comment> does not exist, not replacing. Skipping...");
                return;
            }

            $fromFileName = basename($this->fromFile);
            $this->out->writeln("Moving included file <info>$fromFileName</info> to <info>$path</info>");
            if($this->upgrade->dry) return;

            if(!copy($this->fromFile, $path) || !chmod($path, 0644)) {
                throw new \RuntimeException("Failed to copy the file to '$path'.");
            }
        }
    }

?>