<?php

    namespace ZubZet\Framework\Database\Migration\Commands\Traits;

    use Symfony\Component\Console\Output\OutputInterface;

    /**
     * Executed migration state is keyed on the basename, so two files sharing
     * one basename across roots (or subdirectories) would silently swallow one
     * of them. Commands assembling multi-root migration sets refuse to run.
     */
    trait ChecksDuplicateBasenames {

        /** Returns true when duplicates were found (and reported to $out). */
        private function rejectDuplicateBasenames(array $migrationFiles, OutputInterface $out): bool {
            $byBasename = [];
            foreach($migrationFiles as $migrationFile) {
                $byBasename[basename($migrationFile)][] = $migrationFile;
            }

            $foundDuplicates = false;
            foreach($byBasename as $basename => $sources) {
                if(count($sources) < 2) continue;
                $foundDuplicates = true;
                $out->writeln("<error>Duplicate migration filename '$basename' found in multiple roots:</error>");
                foreach($sources as $source) {
                    $out->writeln("<error>- $source</error>");
                }
                $out->writeln("<error>Rename one of them (module migrations should carry a module prefix). Aborting.</error>");
            }
            return $foundDuplicates;
        }
    }

?>
