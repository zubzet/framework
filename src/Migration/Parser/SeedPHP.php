<?php

    namespace ZubZet\Framework\Migration\Parser;

    class SeedPHP {

        public function loadPhpSeed($filePath) {
            $className = $this->requireSeedClass($filePath);

            $seedInstance = new $className();
            $seedInstance->run();

            return $seedInstance->queries;
        }

        private function requireSeedClass(string $filePath): string {
            $className = pathinfo($filePath, PATHINFO_FILENAME);

            require_once $filePath;

            return $className;
        }
    }