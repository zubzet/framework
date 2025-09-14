<?php

    namespace ZubZet\Tooling\Modifiers;

    class RemoveFile extends BaseModifier {

        public function from(string $file) {
            if(!file_exists($file)) {
                $this->out->writeln("File <comment>$file</comment> already removed. Skipping...");
                return;
            }

            $this->out->writeln("Removing file <info>$file</info>");
            if($this->upgrade->dry) return;
            if(!unlink($file)) {
                throw new \RuntimeException("Failed to remove the file '$file'.");
            }
        }
    }

?>