<?php

    namespace ZubZet\Tooling\Modifiers;

    class JsonModifier extends BaseModifier {

        private bool $isOptional = false;
        public function optional(): void {
            $this->isOptional = true;
        }

        private array $data = [];
        private string $file;

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

        public function modify(\Closure $callback): void {
            $modifiedData = $callback($this->data);

            if($modifiedData === $this->data || is_null($modifiedData)) {
                $this->out->writeln("No changes made to <comment>{$this->file}</comment>. Skipping...");
                return;
            }

            $this->out->writeln("Modifying JSON file <comment>{$this->file}</comment>...");
            if($this->upgrade->dry) return;

            file_put_contents(
                $this->file,
                json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            );
        }
    }
?>