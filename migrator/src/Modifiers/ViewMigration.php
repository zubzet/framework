<?php

    namespace ZubZet\Tooling\Modifiers;

    use ZubZet\Tooling\Blade\LegacyViewConverter;

    /**
     * Migrates legacy "return type" views (and layouts) to Katana .blade.php
     * documents. Introduced for the 1.3.0 render-engine change.
     *
     * For every *.php file found under the configured directories that looks like
     * a legacy return[...] view, the file content is converted and written next to
     * it as *.blade.php, and the original *.php is removed.
     *
     * Files that are not legacy views (no top-level return[...]) are left untouched.
     */
    class ViewMigration extends BaseModifier {

        /** @var array<int, array{from:string, to:string, kind:string, content:string}> */
        private array $planned = [];
        /** @var array<int, array{file:string, reason:string}> */
        private array $skipped = [];

        public function from(string|array $dirs): void {
            if(!is_array($dirs)) $dirs = [$dirs];

            foreach($dirs as $dir) {
                if(!is_dir($dir)) {
                    $this->out->writeln("<comment>Directory '$dir' not found, skipping...</comment>");
                    continue;
                }

                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                );

                foreach($it as $file) {
                    if(!$file->isFile()) continue;
                    if($file->getExtension() !== "php") continue;
                    if(str_ends_with($file->getFilename(), ".blade.php")) continue;

                    $this->consider($file->getPathname());
                }
            }

            $this->out->writeln(sprintf(
                "Found <info>%d</info> legacy view(s) to convert, <comment>%d</comment> file(s) skipped.",
                count($this->planned),
                count($this->skipped),
            ));
        }

        private function consider(string $path): void {
            $source = file_get_contents($path);
            if(false === $source) {
                $this->skipped[] = ["file" => $path, "reason" => "unreadable"];
                return;
            }

            if(!LegacyViewConverter::isLegacyView($source)) {
                $this->skipped[] = ["file" => $path, "reason" => "not a legacy return[...] view"];
                return;
            }

            $target = substr($path, 0, -strlen(".php")) . ".blade.php";
            if(file_exists($target)) {
                $this->skipped[] = ["file" => $path, "reason" => "target already exists: " . basename($target)];
                return;
            }

            try {
                $content = LegacyViewConverter::convertFile($source);
            } catch(\Throwable $e) {
                $this->skipped[] = ["file" => $path, "reason" => "conversion failed: " . $e->getMessage()];
                return;
            }

            $this->planned[] = [
                "from" => $path,
                "to" => $target,
                "kind" => LegacyViewConverter::isLayout($source) ? "layout" : "view",
                "content" => $content,
            ];
        }

        public function migrate(): void {
            foreach($this->skipped as $skip) {
                $this->out->writeln("  <comment>skip</comment> {$skip['file']} ({$skip['reason']})");
            }

            if(count($this->planned) === 0) {
                $this->out->writeln("No legacy views to convert.");
                return;
            }

            foreach($this->planned as $plan) {
                $this->out->writeln(sprintf(
                    "  <info>%s</info> %s -> %s",
                    $plan["kind"],
                    $plan["from"],
                    basename($plan["to"]),
                ));
            }

            $this->out->writeln("");
            $this->out->writeln(sprintf(
                "ZubZet 1.3.0 replaces the return-type view renderer with Katana (.blade.php).",
            ));
            $this->out->writeln("The listed views will be converted and the original .php files removed.");

            if($this->upgrade->dry) {
                $this->out->writeln("<comment>Dry-run: no files written.</comment>");
                return;
            }

            if(!$this->confirmAutomatedChange()) {
                $this->abortRequiringUserAction();
                return;
            }

            foreach($this->planned as $plan) {
                if(false === file_put_contents($plan["to"], $plan["content"])) {
                    throw new \RuntimeException("Failed to write '{$plan['to']}'.");
                }
                if(!unlink($plan["from"])) {
                    throw new \RuntimeException("Failed to remove '{$plan['from']}'.");
                }
                $this->out->writeln("  <info>converted</info> " . basename($plan["to"]));
            }

            $this->out->writeln(sprintf("<info>Converted %d view(s) to .blade.php.</info>", count($this->planned)));
        }
    }

?>
