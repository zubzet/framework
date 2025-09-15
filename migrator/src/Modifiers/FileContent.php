<?php

    namespace ZubZet\Tooling\Modifiers;

    use SebastianBergmann\Diff\Differ;
    use ZubZet\Tooling\Interactions\CommandInteraction;
    use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

    class FileContent extends BaseModifier {
        use CommandInteraction;

        protected string $fileContent;
        protected string $filePath;

        private bool $isOptional = false;
        public function optional() {
            $this->isOptional = true;
        }

        private bool $isOptionalIfNotFound = false;
        public function optionalIfNotFound() {
            $this->isOptionalIfNotFound = true;
        }

        private string $searchedFile;
        public function find(string $file, ?string $folder = null): void {
            $this->searchedFile = $file;
            $folder ??= './';
            $folder = rtrim($folder, DIRECTORY_SEPARATOR);
            $maxDepth = 10;

            $setFound = function (string $path): void {
                $this->filePath = $path;
                $this->fileContent = file_get_contents($path);
            };

            // Fast check at depth 0
            $atRoot = $folder . DIRECTORY_SEPARATOR . $file;
            if (is_file($atRoot)) {
                $setFound($atRoot);
                return;
            }

            // BFS over directories to prefer shallower paths
            $q = new \SplQueue();
            $q->enqueue([$folder, 0]);

            while (!$q->isEmpty()) {
                [$dir, $depth] = $q->dequeue();
                if($depth > $maxDepth) continue;

                try {
                    $it = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
                } catch (\UnexpectedValueException $e) {
                    // unreadable dir — skip
                    continue;
                }

                foreach($it as $info) {
                    if($info->isFile()) {
                        if($info->getFilename() === $file) {
                            // first hit is the closest thanks to BFS
                            $setFound($info->getPathname());
                            return;
                        }
                        continue;
                    }

                    if($info->isDir() && $depth < $maxDepth) {
                        $q->enqueue([$info->getPathname(), $depth + 1]);
                    }
                }
            }

            if($this->isOptionalIfNotFound) {
                $this->isOptional = true;
                $this->out->writeln("<comment>File '$file' not found in folder '$folder', skipping...</comment>");
            }

            if($this->isOptional) return;

            throw new \RuntimeException("File '$file' not found in folder '$folder'");
        }

        private bool $shouldChange = false;
        public function shouldChangeIfPattern(string $pattern): void {
            if(preg_match($pattern, $this->fileContent)) {
                $this->shouldChange = true;
            }
        }

        public function shouldChangeIfIncludes(string $search): void {
            if(str_contains($this->fileContent, $search)) {
                $this->shouldChange = true;
            }
        }

        public function shouldChangeIfNotIncludes(string $search): void {
            if(!str_contains($this->fileContent, $search)) {
                $this->shouldChange = true;
            }
        }

        private ?\Closure $automatedChange = null;
        public function automateChange(\Closure $change): void {
            $this->automatedChange = $change;
        }

        private ?string $automatedChangeCmd = null;
        public function automateChangeCmd(string $command): void {
            $this->automatedChangeCmd = $command;
        }

        public function demandChange(string|array $explanation): void {
            if(!$this->shouldChange) {
                $this->out->writeln("No changes required for <comment>{$this->searchedFile}</comment>, skipping...");
                return;
            }

            $filePath = realpath($this->filePath) ?: $this->filePath;
            $this->out->writeln("<error>Concerning file: $filePath</error>");
            $this->out->writeln("");

            if(!is_array($explanation)) $explanation = [$explanation];
            $explanation = array_map("trim", $explanation);
            $explanation = implode("\n", $explanation);

            $this->out->writeln("<error>$explanation</error>");

            // Propose automated command if available
            if(!is_null($this->automatedChangeCmd)) {
                if($this->runCommand($this->automatedChangeCmd)) {
                    return;
                }
            }

            // Propose an automated change if available
            if(!is_null($this->automatedChange)) {
                // Ask the user to confirm if they want to apply an automated change
                $this->out->writeln("");
                $this->out->writeln("An automated change is available to fix this issue.");

                // Show diff changes
                $contentBefore = $this->fileContent;
                $this->fileContent = ($this->automatedChange)($this->fileContent);

                $outputBuilder = new UnifiedDiffOutputBuilder("--- Original\n+++ Updated\n", false);

                $differ = new Differ($outputBuilder);
                $diff = $differ->diff($contentBefore, $this->fileContent);

                $this->out->writeln("");
                $this->out->writeln("<info>Diff changes:</info>");
                $this->out->writeln($diff);

                // Save the change
                if($this->confirmAutomatedChange()) {
                    $this->out->writeln("<info>Automated change applied to <comment>{$this->searchedFile}</comment></info>");
                    file_put_contents($this->filePath, $this->fileContent);
                    return;
                }
            }

            if(!$this->isOptional) {
                $this->abortRequiringUserAction();
            }
        }
    }

?>