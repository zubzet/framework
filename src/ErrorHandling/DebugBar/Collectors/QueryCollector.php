<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar\Collectors;

    use ZubZet\Framework\Core\Model;

    use DebugBar\DataCollector\AssetProvider;
    use DebugBar\DataCollector\DataCollector;
    use DebugBar\DataCollector\Renderable;

    class QueryCollector extends DataCollector implements Renderable, AssetProvider {

        private array $executedQueries = [];

        public function addQuery(string $sql, float $durationSeconds = 0.0, int $rowCount = 0, array $values = [], ?Model $model = null): void {
            // Optionally skip collecting internal queries based on configuration
            $hideInternals = config("debugbar_hide_internal_queries", default: true);
            $isInternal = $model?->isInternalModel ?? false;
            if($hideInternals && $isInternal) return;

            // Fix indentation for better readability in the debug bar
            $sqlFormatted = preg_replace('/^[ \t]+/m', '', trim($sql));

            $entry = [
                "sql" => $this->interpolatePlaceholders($sqlFormatted, $values),
                "duration" => $durationSeconds,
                "duration_str" => $this->getDataFormatter()->formatDuration($durationSeconds),
                "row_count" => $rowCount,
                "is_success" => true,
                "params" => (object) $values,
            ];

            $this->executedQueries[] = $entry;
        }

        private function interpolatePlaceholders(string $sql, array $values): string {
            // This is a rather simple placeholder detection, but cheap and not complex, perfect for debug
            // Skip ? inside single/double-quoted literals, -- line comments, /* */ block comments
            $pattern = '/\'[^\']*\'|"[^"]*"|--[^\n]*|\/\*.*?\*\/|(\?)/s';

            // Replace ? placeholders with actual values
            return preg_replace_callback($pattern, function($match) use (&$values) {
                if(!isset($match[1])) return $match[0];
                if(empty($values)) return "?";
                $value = array_shift($values);
                return is_null($value) ? "NULL" : "'" . addslashes((string) $value) . "'";
            }, $sql);
        }

        public function getName() {
            return "queries";
        }

        public function collect(): array {
            $total = array_sum(array_column($this->executedQueries, "duration"));
            return [
                "nb_statements" => count($this->executedQueries),
                "nb_failed_statements" => 0,
                "accumulated_duration" => $total,
                "accumulated_duration_str" => $this->getDataFormatter()->formatDuration($total),
                "statements" => $this->executedQueries,
            ];
        }

        public function getAssets() {
            return [
                'css' => [
                    'widgets/sqlqueries/widget.css',
                ],
                'js' => [
                    'widgets/sqlqueries/widget.js',
                ],
            ];
        }

        public function getWidgets(): array {
            return [
                "queries" => [
                    "icon" => "database",
                    "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                    "map" => "queries",
                    "default" => "[]",
                ],
                "queries:badge" => [
                    "map" => "queries.nb_statements",
                    "default" => 0,
                ],
            ];
        }

    }

?>
