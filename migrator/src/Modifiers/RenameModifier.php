<?php

    namespace ZubZet\Tooling\Modifiers;

    class RenameModifier extends BaseModifier {

        public function from(string|array $files, string|array $explanation, ?\Closure $renameCallback = null): void {
            if(!is_array($files)) $files = [$files];

            if(count($files) === 0) {
                $this->out->writeln("No files provided for rename, skipping...");
                return;
            }

            // Normalize explanation to a single string
            if(!is_array($explanation)) $explanation = [$explanation];
            $explanation = array_map("trim", $explanation);
            $explanation = implode("\n", $explanation);

            // Validate each file
            foreach($files as $file) {
                if(!file_exists($file)) {
                    $this->out->writeln("File <comment>$file</comment> not found. Skipping...");
                    continue;
                }

                // Explain the issue and the required change to the user
                $this->out->writeln("<error>Concerning file: $file</error>");
                $this->out->writeln("");
                $this->out->writeln("<error>$explanation</error>");

                // Call the callback using the filename and path
                $newName = null;
                if(!is_null($renameCallback)) {
                    $newName = ($renameCallback)(basename($file), dirname($file));
                }

                // User action is required as no callback is given or the callback was not able to do anything and returned null
                if(is_null($newName)) {
                    $this->out->writeln("No automated rename available for <comment>$file</comment>.");
                    $this->abortRequiringUserAction();
                }

                // Make sure the newName also works with the full path
                $newName = (dirname($file) . DIRECTORY_SEPARATOR . basename($newName));

                // Abort when the target file already exists
                if(file_exists($newName)) {
                    $this->out->writeln("File already exists <comment>$newName</comment>.");
                    $this->abortRequiringUserAction();
                }

                // Show suggested automated rename and confirm with user
                $this->out->writeln("");
                $this->out->writeln("Suggested automated rename:");
                $this->out->writeln("<info>$file</info> -> <info>$newName</info>");

                // if the user does not confirm, abort and require manual action
                if(!$this->confirmAutomatedChange()) {
                    $this->abortRequiringUserAction();
                    continue;
                }

                $this->out->writeln("Renaming file <info>$file</info> -> <info>$newName</info>");
                if($this->upgrade->dry) continue;

                if(!rename($file, $newName)) {
                    throw new \RuntimeException("Failed to rename file '$file' to '$newName'.");
                }
            }
        }
    }

?>