<?php

    namespace ZubZet\Framework\Support\DebugBar\Collectors;

    use DebugBar\DataCollector\AssetProvider;
    use DebugBar\DataCollector\DataCollector;
    use DebugBar\DataCollector\Renderable;
    use mysqli;

    class QueryCollector extends DataCollector implements Renderable, AssetProvider {

        private array $executedQueries = [];

        public function addQuery(mysqli $mysqli, string $sql, float $durationSeconds = 0.0, int $rowCount = 0, array $arguments = [], array $resultData = []): void {
            // filter out queries that shouldn't be logged
            if($this->skipQuery($sql)) return;

            if(!empty($resultData)) {
                $arguments[] = "";
                $arguments[] = "Results:";
                foreach($resultData as $row) {
                    $arguments[] = json_encode($row);
                }
            }

            $sqlFormatted = preg_replace('/^[ \t]+/m', '', trim($sql));

            $entry = [
                "sql" => $this->interpolatePlaceholders($mysqli, $sqlFormatted, $arguments),
                "duration" => $durationSeconds,
                "duration_str" => $this->getDataFormatter()->formatDuration($durationSeconds),
                "row_count" => $rowCount,
                "is_success" => true,
                "params" => (object) $arguments,
            ];

            $this->executedQueries[] = $entry;
        }

        private function skipQuery(string $sql): bool {
            $skipInternals = config("debugbar_hide_internal_queries", default: false);
            if(!$skipInternals) return false;

            // Match queries that modify the database structure or data on tables starting with "z_"
            // DELETE, UPDATE, INSERT, ALTER, DROP, CREATE statements on tables starting with "z_"
            return preg_match('~\b(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER\s+TABLE|DROP\s+TABLE|CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?)\s+(?:(?:`\w+`|"\w+"|\[\w+\]|\w+)\s*\.\s*)?(?:`z_\w+`|"z_\w+"|\[z_\w+\]|\bz_\w+\b)~i', $sql);
        }

        private function interpolatePlaceholders(mysqli $mysqli, string $sql, array $arguments): string {
            // no placeholders or no arguments, return the original SQL
            if(count($arguments) < 2) return $sql;

            // The first argument is expected to be the types string (e.g., "ssi"), followed by the bound values
            $types = $arguments[0];
            if(!is_string($types) || $types === "") return $sql;

            $values = array_slice($arguments, 1);
            $valueCount = count($values);
            $index = 0;
            $length = strlen($sql);
            $result = "";

            // Iterate through the SQL string and replace placeholders with bound values
            for($i = 0; $i < $length; $i++) {
                $char = $sql[$i];

                // Only replace "?" placeholders if we have enough values and types
                if($char != "?" || $index >= $valueCount) {
                    $result .= $char;
                    continue;
                }

                // Replace the "?" with the corresponding bound value, properly escaped and formatted based on its type
                $result .= $this->formatBoundValue($mysqli, $values[$index], $types[$index] ?? "s");
                $index++;
            }

            return $result;
        }

        // Format the bound value based on its type for safe interpolation into the SQL query
        private function formatBoundValue(mysqli $mysqli, mixed $value, string $type): string {
            if($value === null) return "NULL";

            // Format the value based on its type: integer, double, blob, or string (default)
            return match($type) {
                "i" => (string)(int)$value,
                "d" => (string)(float)$value,
                "b" => "'<BLOB>'",
                default => '"' . $mysqli->real_escape_string((string)$value) . '"',
            };
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
