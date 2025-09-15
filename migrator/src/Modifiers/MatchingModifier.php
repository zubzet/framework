<?php

    namespace ZubZet\Tooling\Modifiers;

    use ZubZet\Tooling\Issue;

    class MatchingModifier extends BaseModifier {
        private array $matchedFiles = [];
        public function from(array|string $paths) {
            if(!is_array($paths)) $paths = [$paths];

            foreach($paths as $path) {
                if(!is_dir($path)) continue;

                $items = new \RecursiveDirectoryIterator($path);
                foreach(new \RecursiveIteratorIterator($items) as $file) {
                    if($file->isDir()) continue;
                    $this->matchedFiles[] = $file->getRealPath();
                }
            }

            $this->out->writeln("Matched <info>" . count($this->matchedFiles) . "</info> files to check.");
        }

        private int $linesChecked = 0;
        private array $issues = [];

        public function matchLineByLine(string $pattern, string|array $warning) {
            if(!is_array($warning)) $warning = [$warning];

            foreach($this->matchedFiles as $file) {
                $lines = file($file);
                if(false === $lines) continue;

                foreach($lines as $i => $line) {
                    $this->linesChecked++;
                    if(empty($line)) continue;
                    if(0 === preg_match($pattern, $line)) continue;
                    $this->issues[] = new Issue($file, $i + 1, $warning);
                }
            }
        }

        public function warn() {
            $this->out->writeln("Made <info>" . $this->linesChecked . "</info> checks.");

            if(0 == count($this->issues)) return;

            foreach($this->issues as $issue) {
                $this->out->writeln("");
                foreach($issue->messages as $message) {
                    $this->out->writeln("<error>{$message}</error>");
                }
                $this->out->writeln("In: {$issue->file}:{$issue->line}");
            }

            $this->out->writeln("");
            $this->out->writeln("<error>Total issues found: " . count($this->issues) . "</error>");

            if($this->upgrade->dry) return;
            $this->abortRequiringUserAction();
        }
    }

?>