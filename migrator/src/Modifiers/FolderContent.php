<?php

    namespace ZubZet\Tooling\Modifiers;

    use Symfony\Component\Filesystem\Filesystem;
    use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
    use Symfony\Component\Finder\Finder;

    class FolderContent extends BaseModifier     {

        public function move(string $fromPath, string $toPath, bool $allowEmptyFrom = true): void {
            $fs = new Filesystem();

            $fromPath = rtrim($fromPath, DIRECTORY_SEPARATOR);
            $toPath = rtrim($toPath, DIRECTORY_SEPARATOR);

            if(!$fs->exists($fromPath) || !is_dir($fromPath)) {
                if($allowEmptyFrom) {
                    $this->out->writeln("Source folder <comment>$fromPath</comment> does not exist, skipping as allowed.");
                    return;
                }
                throw new \RuntimeException("Source folder '$fromPath' does not exist");
            }

            if((!$fs->exists($toPath) || !is_dir($toPath)) && !$this->upgrade->dry) {
                throw new \RuntimeException("Destination folder '$toPath' does not exist");
            }

            $fromReal = realpath($fromPath) ?: $fromPath;
            $toReal = realpath($toPath) ?: $toPath;

            if($fromReal === $toReal) {
                throw new \RuntimeException("Source and destination are the same: '$fromReal'.");
            }

            // Guard against mirroring into a descendant of the source
            if (str_starts_with($toReal, $fromReal . DIRECTORY_SEPARATOR)) {
                throw new \RuntimeException("Refusing to move contents into a subdirectory of the source ('$toReal' is inside '$fromReal').");
            }

            // Count what will be moved (recursive)
            $filesCount = iterator_count((new Finder())->files()->in($fromReal));
            $dirsCount = iterator_count((new Finder())->directories()->in($fromReal));

            $this->out->writeln(sprintf(
                "Moving <info>%d</info> file%s and <info>%d</info> director%s from <info>%s</info> to <info>%s</info>.",
                $filesCount, $filesCount === 1 ? '' : 's',
                $dirsCount,  $dirsCount === 1 ? 'y' : 'ies',
                $fromPath,
                $toPath,
            ));

            // Only do the work if not a dry run
            if ($this->upgrade->dry) return;

            try {
                $fs->mirror($fromReal, $toReal, null, [
                    'override' => true,
                    'delete' => false,
                    'copy_on_windows' => true,
                ]);

                // Remove the content in the source directory
                foreach ((new Finder())->depth('== 0')->in($fromReal) as $child) {
                    $fs->remove($child->getPathname());
                }
            } catch (IOExceptionInterface $e) {
                throw new \RuntimeException("Failed to move folder contents: " . $e->getMessage(), 0, $e);
            }
        }

        public function moveWithParentFolder(string $fromPath, string $toPath, bool $ignoreIfMissing = true): void {
            if(!is_dir($fromPath)) {
                if($ignoreIfMissing) {
                    $this->out->writeln("Source folder <comment>$fromPath</comment> does not exist, skipping as allowed.");
                    return;
                }
                throw new \RuntimeException("Source folder '$fromPath' does not exist");
            }

            if(!is_dir($toPath)) {
                if(!mkdir($toPath, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory '$toPath'.");
                }
            }

            $this->move($fromPath, $toPath);

            // Remove the source directory
            if($this->upgrade->dry) return;
            rmdir($fromPath);
        }
    }

?>