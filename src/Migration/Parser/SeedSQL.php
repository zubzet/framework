<?php
    namespace ZubZet\Framework\Migration\Parser;

    // Loads a PHP migration file
    class SeedSQL {

        public function loadSqlFile(string $filePath): array {
            $content = file_get_contents($filePath);
            return($content && trim($content) !== "") ? [$content] : [];
        }

    }
