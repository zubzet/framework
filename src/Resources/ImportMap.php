<?php

    namespace ZubZet\Framework\Resources;

    /**
     * Represents a JavaScript import map and knows how to render it.
     *
     * Entries are keyed by the module name used in `import ... from 'name'`.
     * Each entry is an array with one of:
     *   ['path' => 'logical/path.js']          Resolved through the AssetMapper
     *   ['package' => 'vendor/name',           Resolved via a Composer package prefix
     *    'file' => 'dist/file.js']
     *   ['url' => 'https://.../module.js']     Used verbatim (CDN, etc.)
     *
     * An additional `entrypoint => true` flag marks modules that should be
     * preloaded and auto-imported on page load.
     */
    class ImportMap {

        private AssetMapper $mapper;

        /** @var array<string, array<string, mixed>> */
        private array $entries = [];

        public function __construct(AssetMapper $mapper) {
            $this->mapper = $mapper;
        }

        public function loadFromFile(string $configFile): void {
            if (!is_file($configFile)) return;
            $entries = include $configFile;
            if (!is_array($entries)) return;
            foreach ($entries as $name => $entry) {
                if (!is_array($entry)) continue;
                $this->set((string) $name, $entry);
            }
        }

        /**
         * @param array<string, mixed> $entry
         */
        public function set(string $name, array $entry): void {
            $this->entries[$name] = $entry;
        }

        /**
         * @return array<string, array<string, mixed>>
         */
        public function all(): array {
            return $this->entries;
        }

        /**
         * Render the <script type="importmap"> block plus preload and entrypoint
         * tags. Extra module names can be supplied to force their import without
         * changing the stored entry.
         *
         * @param array<int, string> $extraEntrypoints
         */
        public function render(array $extraEntrypoints = []): string {
            $imports = [];
            foreach ($this->entries as $name => $entry) {
                $url = $this->resolveEntry($entry);
                if (null === $url) continue;
                $imports[$name] = $url;
            }

            $entrypoints = $this->collectEntrypoints($extraEntrypoints);

            if (empty($imports) && empty($entrypoints)) return "";

            $lines = [];
            $json = (string) json_encode(
                ['imports' => $imports],
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            );
            $lines[] = '<script type="importmap">' . "\n" . $json . "\n" . '</script>';

            foreach ($entrypoints as $name) {
                if (!isset($imports[$name])) continue;
                $href = htmlspecialchars($imports[$name], ENT_QUOTES);
                $lines[] = "<link rel=\"modulepreload\" href=\"$href\">";
            }

            foreach ($entrypoints as $name) {
                if (!isset($imports[$name])) continue;
                $lines[] = '<script type="module">import ' . json_encode($name) . ';</script>';
            }

            return implode("\n", $lines);
        }

        /**
         * @param array<int, string> $extra
         * @return array<int, string>
         */
        private function collectEntrypoints(array $extra): array {
            $out = [];
            foreach ($this->entries as $name => $entry) {
                if (!empty($entry['entrypoint'])) $out[] = (string) $name;
            }
            foreach ($extra as $name) {
                $name = (string) $name;
                if (!in_array($name, $out, true)) $out[] = $name;
            }
            return $out;
        }

        /**
         * @param array<string, mixed> $entry
         */
        private function resolveEntry(array $entry): ?string {
            if (isset($entry['url']) && is_string($entry['url'])) {
                return $entry['url'];
            }
            if (isset($entry['package'], $entry['file']) && is_string($entry['package']) && is_string($entry['file'])) {
                $logicalPath = $entry['package'] . '/' . ltrim($entry['file'], '/');
                return $this->mapper->url($logicalPath);
            }
            if (isset($entry['path']) && is_string($entry['path'])) {
                $path = ltrim($entry['path'], './');
                if (str_starts_with($path, 'assets/')) {
                    $path = substr($path, strlen('assets/'));
                }
                return $this->mapper->url($path);
            }
            return null;
        }
    }

?>
