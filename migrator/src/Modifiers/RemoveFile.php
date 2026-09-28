<?php

    namespace ZubZet\Tooling\Modifiers;

    class RemoveFile extends BaseModifier {

        public function from(string|array $files) {
            if(!is_array($files)) $files = [$files];

            foreach($files as $file) {
                if(!file_exists($file)) {
                    $this->out->writeln("File <comment>$file</comment> already removed. Skipping...");
                    continue;
                }

                $this->out->writeln("Removing file <info>$file</info>");
                if($this->upgrade->dry) continue;

                if(!unlink($file)) {
                    throw new \RuntimeException("Failed to remove the file '$file'.");
                }
            }
        }
    }

?>