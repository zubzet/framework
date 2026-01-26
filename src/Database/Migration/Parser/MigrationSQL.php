<?php
    namespace ZubZet\Framework\Database\Migration\Parser;


    // Loads a SQL migration file
    class MigrationSQL {

        public function extractInformation(string $filePath, array &$sqlBuffer, bool &$skip, string &$environment, bool &$manual): void {
            $sqlContent = file_get_contents($filePath);
            $sqlBuffer = ($sqlContent && trim($sqlContent) !== "") ? [$sqlContent] : [];
        }
    }
