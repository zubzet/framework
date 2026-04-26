<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar\Collectors;

    use DebugBar\DataCollector\AssetProvider;
    use DebugBar\DataCollector\DataCollector;
    use DebugBar\DataCollector\Renderable;

    /**
     * Mirrors the API of DebugBar\DataCollector\TemplateCollector (shipped
     * in php-debugbar v3.x) so we feed PhpDebugBar.Widgets.TemplatesWidget
     * the same shape it expects, without forcing the v3 dependency bump.
     */
    class TemplateCollector extends DataCollector implements Renderable, AssetProvider {

        private array $templates = [];
        private bool $collectData;

        public function __construct(bool $collectData = true) {
            $this->collectData = $collectData;
        }

        public function addTemplate(string $name, array $data, ?string $type = null, ?string $path = null): void {
            $params = $this->collectData
                ? array_map(fn($value) => $this->getDataFormatter()->formatVar($value), $data)
                : [];

            $template = [
                "name" => $name,
                "param_count" => $this->collectData ? count($params) : null,
                "params" => $params,
                "type" => $type,
                "start" => microtime(true),
            ];

            if($path !== null && $this->getXdebugLinkTemplate()) {
                $template["xdebug_link"] = $this->getXdebugLink($path);
            }

            $this->templates[] = $template;
        }

        public function getName(): string {
            return "templates";
        }

        public function collect(): array {
            return [
                "nb_templates" => count($this->templates),
                "templates" => $this->templates,
            ];
        }

        public function getAssets(): array {
            return [
                "css" => "widgets/templates/widget.css",
                "js" => "widgets/templates/widget.js",
            ];
        }

        public function getWidgets(): array {
            return [
                "templates" => [
                    "icon" => "file-code",
                    "widget" => "PhpDebugBar.Widgets.TemplatesWidget",
                    "map" => "templates",
                    "default" => "[]",
                ],
                "templates:badge" => [
                    "map" => "templates.nb_templates",
                    "default" => 0,
                ],
            ];
        }

    }

?>
