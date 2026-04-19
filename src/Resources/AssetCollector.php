<?php

    namespace ZubZet\Framework\Resources;

    /**
     * Collects per-page asset declarations during view rendering and
     * emits the corresponding HTML tags when the layout asks for them.
     */
    class AssetCollector {

        public const CSS_PLACEHOLDER = "<!--@@ZUBZET_ASSETS_CSS@@-->";
        public const JS_PLACEHOLDER = "<!--@@ZUBZET_ASSETS_JS@@-->";
        public const MODULES_PLACEHOLDER = "<!--@@ZUBZET_ASSETS_MODULES@@-->";

        private AssetMapper $mapper;

        /** @var array<int, string> */
        private array $css = [];

        /** @var array<int, array{path: string, attrs: array<string, string>}> */
        private array $js = [];

        /** @var array<int, string> */
        private array $modules = [];

        public function __construct(AssetMapper $mapper) {
            $this->mapper = $mapper;
        }

        public function css(string $path): self {
            if (!\in_array($path, $this->css, true)) {
                $this->css[] = $path;
            }
            return $this;
        }

        /**
         * @param array<string, string|bool> $attrs
         */
        public function js(string $path, array $attrs = []): self {
            foreach ($this->js as $existing) {
                if ($existing['path'] === $path) return $this;
            }
            $this->js[] = ['path' => $path, 'attrs' => $attrs];
            return $this;
        }

        public function module(string $entrypoint): self {
            if (!\in_array($entrypoint, $this->modules, true)) {
                $this->modules[] = $entrypoint;
            }
            return $this;
        }

        /**
         * Emit a placeholder that gets replaced with the full CSS tag list
         * once the layout has finished rendering (so that assets registered
         * during view body execution are included).
         */
        public function renderCss(): string {
            return self::CSS_PLACEHOLDER;
        }

        public function renderJs(): string {
            return self::JS_PLACEHOLDER;
        }

        public function renderModules(): string {
            return self::MODULES_PLACEHOLDER;
        }

        /**
         * Replace placeholders in the rendered HTML with the final tag markup.
         */
        public function finalize(string $html): string {
            return strtr($html, [
                self::CSS_PLACEHOLDER => $this->buildCssTags(),
                self::JS_PLACEHOLDER => $this->buildJsTags(),
                self::MODULES_PLACEHOLDER => $this->buildModuleTags(),
            ]);
        }

        private function buildModuleTags(): string {
            if (empty($this->modules) && empty($this->mapper->importMap()->all())) {
                return "";
            }
            return $this->mapper->importMap()->render($this->modules);
        }

        private function buildCssTags(): string {
            $tags = [];
            foreach ($this->css as $path) {
                $url = htmlspecialchars($this->mapper->url($path), ENT_QUOTES);
                $tags[] = "<link rel=\"stylesheet\" href=\"$url\">";
            }
            return implode("\n", $tags);
        }

        private function buildJsTags(): string {
            $tags = [];
            foreach ($this->js as $entry) {
                $url = htmlspecialchars($this->mapper->url($entry['path']), ENT_QUOTES);
                $extra = $this->renderAttrs($entry['attrs']);
                $tags[] = "<script src=\"$url\"$extra></script>";
            }
            return implode("\n", $tags);
        }

        /**
         * @return array<int, string>
         */
        public function getModules(): array {
            return $this->modules;
        }

        /**
         * @param array<string, string|bool> $attrs
         */
        private function renderAttrs(array $attrs): string {
            $parts = [];
            foreach ($attrs as $key => $value) {
                if (false === $value) continue;
                if (true === $value) {
                    $parts[] = " " . htmlspecialchars((string) $key, ENT_QUOTES);
                    continue;
                }
                $parts[] = \sprintf(
                    ' %s="%s"',
                    htmlspecialchars((string) $key, ENT_QUOTES),
                    htmlspecialchars((string) $value, ENT_QUOTES)
                );
            }
            return implode("", $parts);
        }
    }
