<?php

    class z_adminDashboardModel extends z_model {

        public function getTableStatus(): array {
            $sql = "SHOW TABLE STATUS";
            $this->exec($sql);
            $status["tables"] = $this->resultToArray();

            // Order by row count
            usort(
                $status["tables"],
                fn($a, $b) => (int) $b['Rows']  <=> (int) $a['Rows'],
            );

            // Approximate total row count
            $status["approxRows"] = array_reduce(
                $status["tables"],
                fn($carry, $t) => $carry + (int)($t['Rows'] ?? 0),
            );

            return $status;
        }

        public function getRowStatus(string $table, ?int $page = null): array {
            // Validate the table name
            $tables = array_map(
                fn($t) => $t["Name"],
                $this->getTableStatus()["tables"],
            );

            if(!in_array($table, $tables)) {
                throw new \InvalidArgumentException("Invalid table name: $table");
            }

            // Column information
            $sql = "SHOW COLUMNS FROM `$table`";
            $columns = $this->exec($sql)->resultToArray();

            // Find a column to order by
            $orderBy = $columns[0]["Field"];
            foreach($columns as $col) {
                if("PRI" !== $col["Key"]) continue;
                $orderBy = $col["Field"];
            }

            // Pagination
            $perPage = 20;
            $limit = is_null($page) ? 10000 : $perPage;
            $offset = (($page ?? 0) - 1) * $perPage;

            // Request the data
            $sql = $this->dbSelect(["*"])
                        ->from($table)
                        ->orderDesc($orderBy)
                        ->limit($limit)
                        ->offset($offset);
            $rows = $this->exec($sql)->resultToArray();

            // Hide sensitive values
            foreach($rows as &$row) {
                foreach(["password", "pw", "salt", "hash"] as $key) {
                    if(!array_key_exists($key, $row)) continue;
                    $row[$key] = str_repeat("*", 8);
                }
            }

            // Find total row count
            $this->exec($this->dbSelect(['total' => 'COUNT(*)'])->from($table));
            $totalRows = $this->resultToLine()["total"] ?? 0;

            return [
                "name" => $table,
                "rows" => $rows,
                "columns" => $columns,
                "totalRows" => $totalRows,
                "totalColumns" => count($columns),
                "totalPages" => max(1, (int) ceil($totalRows / $perPage)),
                "orderBy" => $orderBy,
            ];
        }

        public function exportToCsv(array $table) {
            $filename = "export_" . date("Y-m-d_H-i-s") . ".csv";

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if(empty($table["rows"])) {
                fclose($out);
                return;
            }

            fputcsv(
                $out,
                array_keys(reset($table["rows"])),
                ",", "\"", "\\",
            );

            foreach($table["rows"] as $row) {
                fputcsv($out, $row, ",", "\"", "\\");
            }

            fclose($out);
        }
    }

?>