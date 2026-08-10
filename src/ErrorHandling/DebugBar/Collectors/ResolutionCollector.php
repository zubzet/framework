<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar\Collectors;

    use DebugBar\DataCollector\DataCollector;
    use DebugBar\DataCollector\Renderable;

    /**
     * Provenance for every convention lookup the Registry performed this
     * request: which root (userspace, a module, or the framework) won each
     * controller, model, and route file. Makes shadowing visible per page.
     */
    class ResolutionCollector extends DataCollector implements Renderable {

        private array $resolutions = [];

        public function addResolution(string $kind, string $name, string $origin, string $path): void {
            $this->resolutions["$kind: $name"] = "$origin ($path)";
        }

        public function getName(): string {
            return "resolutions";
        }

        public function collect(): array {
            return [
                "count" => count($this->resolutions),
                "resolutions" => $this->resolutions,
            ];
        }

        public function getWidgets(): array {
            return [
                "resolutions" => [
                    "icon" => "random",
                    "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                    "map" => "resolutions.resolutions",
                    "default" => "{}",
                ],
                "resolutions:badge" => [
                    "map" => "resolutions.count",
                    "default" => 0,
                ],
            ];
        }

    }

?>
