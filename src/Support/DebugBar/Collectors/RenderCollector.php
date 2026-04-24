<?php

    namespace ZubZet\Framework\Support\DebugBar\Collectors;

    use DebugBar\DataCollector\DataCollector;
    use DebugBar\DataCollector\Renderable;

    class RenderCollector extends DataCollector implements Renderable {

        private array $renders = [];

        public function addRender(string $document, array $opt, string $layout = ""): void {
            $formattedMessaged = "Document: `$document` \nLayout: `$layout` \nOptions: " . json_encode($opt) . "]";

            $this->renders[] = [
                "message" => $formattedMessaged,
                "is_string" => false,
                "label" => "render",
                "time" => microtime(true),
            ];
        }

        public function getName() {
            return "renders";
        }

        public function collect(): array {
            return [
                "count" => count($this->renders),
                "messages" => $this->renders,
            ];
        }

        public function getWidgets(): array {
            $name = $this->getName();
            return [
                $name => [
                    "icon" => "eye",
                    "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                    "map" => "$name.messages",
                    "default" => "[]",
                ],
                "$name:badge" => [
                    "map" => "$name.count",
                    "default" => "null",
                ],
            ];
        }

    }

?>
