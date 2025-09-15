<?php

    namespace ZubZet\Tooling\Modifiers;

    class JsonModifier extends BaseModifier {

        private bool $isOptional = false;
        public function optional(): void {
            $this->isOptional = true;
        }

        protected array $data = [];
        protected string $file;

        public function from(string $file) {
            if(!file_exists($file)) {
                if($this->isOptional) {
                    $this->out->writeln("JSON file <comment>$file</comment> not found. Skipping...");
                    return;
                }

                throw new \RuntimeException("JSON file '$file' not found.");
            }

            $this->file = $file;

            $content = file_get_contents($file);
            $this->data = json_decode($content, true);

            if(is_null($this->data) || json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Failed to parse JSON file '$file'.");
            }
        }

        private bool $hasBeenModified = false;
        public function modify(\Closure $callback): void {
            if(!isset($this->file)) {
                $this->out->writeln("No JSON file loaded, skipping modification.");
                return;
            }

            $modifiedData = $callback($this->data);

            $noChangesMade = is_null($modifiedData);

            $sourceData = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $modifiedData = json_encode($modifiedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $noChangesMade = $noChangesMade || $modifiedData === $sourceData;
            if($noChangesMade) {
                $this->out->writeln("No changes made to <comment>{$this->file}</comment>. Skipping...");
                return;
            }

            $this->out->writeln("Modifying JSON file <info>{$this->file}</info>...");
            $this->hasBeenModified = true;

            if($this->upgrade->dry) return;
            file_put_contents($this->file, $modifiedData);
        }

        public function ifModified(\Closure $callback): void {
            if(!$this->hasBeenModified) return;
            $callback();
        }
    }
?>