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

        public function addTemplate(string $name, array $data, string $type, string $layout): void {
            $params = array_map(fn($value) => $this->getDataFormatter()->formatVar($value), $data);

            $this->templates[] = [
                "name" => "$name (layout: $layout)",
                "param_count" => count($params),
                "params" => $params,
                "type" => $type,
                "layout" => $layout,
                "start" => microtime(true),
            ];
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
