<?php

    namespace ZubZet\Framework\Testing\Coverage;

    use SebastianBergmann\CodeCoverage\CodeCoverage;
    use SebastianBergmann\CodeCoverage\Node\Directory;
    use SebastianBergmann\CodeCoverage\Node\File;
    use SebastianBergmann\CodeCoverage\Report\Text;

    /**
     * @internal
     * @codeCoverageIgnore Only invoked after measurement has stopped (--cli mode); coverage cannot record this.
     */
    final class TextReport {

        private const COLOR_GREEN = "\x1b[32m";
        private const COLOR_YELLOW = "\x1b[33m";
        private const COLOR_RED = "\x1b[31m";
        private const COLOR_DIM = "\x1b[2m";
        private const COLOR_RESET = "\x1b[0m";

        public function process(CodeCoverage $coverage): string {
            // Generate summary first to determine color coding for files
            $summary = (new Text(
                lowUpperBound: 50,
                highLowerBound: 90,
                showUncoveredFiles: true,
                showOnlySummary: true,
            ))->process($coverage, true);

            // Render branch structure with color-coded coverage percentages
            return $summary . $this->renderDirectory($coverage->getReport());
        }

        private function renderDirectory(Directory $dir, string $indent = ''): string {
            $output = '';
            $children = array_merge($dir->directories(), $dir->files());
            $total = count($children);

            foreach($children as $index => $child) {
                // Check if this is the last child to determine connector type
                // This helps in visually representing the tree structure
                $isLast = $index === $total - 1;

                $connector = '├── ';
                $extension = '│   ';

                // If it's the last child, use a different connector and extension
                if($isLast) {
                    $connector = '└── ';
                    $extension = '    ';
                }

                // Recursively render subdirectories
                if($child instanceof Directory) {
                    $output .= self::COLOR_DIM . "{$indent}{$connector}" . $child->name() . '/' . self::COLOR_RESET . PHP_EOL;
                    $output .= $this->renderDirectory($child, "{$indent}{$extension}");
                    continue;
                }

                // Render files with coverage info
                $output .= $this->renderFile($child, $indent . $connector);
            }

            return $output;
        }

        private function renderFile(File $file, string $prefix): string {
            $name = $file->name();
            $execLines = $file->numberOfExecutableLines();
            $covLines = $file->numberOfExecutedLines();

            // Calculate coverage percentage
            $percentage = 100.0;
            if($execLines > 0) {
                $percentage = ($covLines / $execLines) * 100;
            }

            $color = match(true) {
                $percentage >= 90 => self::COLOR_GREEN,
                $percentage >= 50 => self::COLOR_YELLOW,
                default => self::COLOR_RED,
            };

            $coverage = sprintf('%5.1f%%', $percentage) . " ({$covLines}/{$execLines})";

            return "{$prefix} {$color}{$name}" . self::COLOR_RESET . "  {$coverage}" . PHP_EOL;
        }
    }
