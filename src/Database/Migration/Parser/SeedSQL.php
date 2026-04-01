<?php
    namespace ZubZet\Framework\Database\Migration\Parser;

    // Loads a PHP migration file
    class SeedSQL {

        public function loadSqlFile(string $filePath): string {
            return file_get_contents($filePath);;
        }

    }
