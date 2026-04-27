<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar\Collectors;

    use ZubZet\Framework\ErrorHandling\DebugBar\CanFormatValue;

    use DebugBar\Bridge\MonologCollector as BaseMonologCollector;

    class MonologCollector extends BaseMonologCollector {

        use CanFormatValue;

        protected function write($record): void {
            $fields = array_merge(
                $this->prefixKeys("context", $record["context"] ?? []),
                $this->prefixKeys("extra", $record["extra"] ?? []),
            );

            $this->records[] = [
                "message" => $this->buildSearchable($record, $fields),
                "message_html" => $this->buildHtml($record, $fields),
                "is_string" => true,
                "label" => strtolower($record["level_name"]),
                "time" => $record["datetime"]->format("U"),
            ];
        }

        private function prefixKeys(string $prefix, array $data): array {
            $out = [];
            foreach($data as $key => $value) {
                $out["{$prefix}.$key"] = $this->formatValue($value);
            }
            return $out;
        }

        private function buildSearchable(array $record, array $fields): string {
            $parts = [
                $record["extra"]["traceId"] ?? "",
                $record["channel"] ?? "",
                $record["level_name"] ?? "",
                $record["message"] ?? "",
            ];

            foreach($fields as $key => $value) {
                $parts[] = "$key=$value";
            }

            return implode(" ", array_filter($parts));
        }

        private function buildHtml(array $record, array $fields): string {
            $traceId = $record["extra"]["traceId"] ?? "";
            $channel = $record["channel"] ?? "";
            $levelName = $record["level_name"] ?? "";
            $message = $record["message"] ?? "";

            $headline = e("$traceId [$channel $levelName]: $message");

            if(empty($fields)) return $headline;

            $rows = "";
            foreach($fields as $key => $value) {
                $isContext = str_starts_with($key, "context.") ? "primary" : "info";
                $key = e($key);
                $value = e($value);

                $rows .= <<<HTML
                    <tr>
                        <td class="pr-3 text-{$isContext}">{$key}</td>
                        <td><pre>{$value}</pre></td>
                    </tr>
                HTML;
            }

            return <<<HTML
                <details>
                    <summary style="cursor:pointer;">
                        {$headline}
                    </summary>
                    <table class="phpdebugbar-widgets-params">
                        {$rows}
                    </table>
                </details>
            HTML;
        }
    }

?>
