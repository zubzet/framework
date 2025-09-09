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

            // TODO: Find the primary key to order by or default to the first column
            $orderBy = $columns[0]["Field"];


            $types = "";
            $params = [];

            // Request the data
            $sql = "SELECT
                        *,
                        (SELECT COUNT(*) FROM `$table`) AS totalRows
                    FROM `$table`
                    ORDER BY `$orderBy` DESC";


            if(!is_null($page)) {
                $perPage = 20;
                $offset = ($page - 1) * $perPage;

                $sql .= "
                        LIMIT ?
                        OFFSET ?";

                $types = "ii";
                $params = [$perPage, $offset];
            }


            if(empty($types)) {
                $rows = $this->exec($sql)->resultToArray();
            } else {
                $rows = $this->exec($sql, $types, ...$params)->resultToArray();
            }

            $totalRows = $rows[0]["totalRows"];

            // Hide sensitive values
            foreach($rows as &$row) {
                unset($row["totalRows"]);

                foreach(["password", "pw", "salt", "hash"] as $key) {
                    if(!array_key_exists($key, $row)) continue;
                    $row[$key] = str_repeat("*", 8);
                }
            }

            return [
                "name" => $table,
                "rows" => $rows,
                "columns" => $columns,
                "totalRows" => $totalRows,
            ];
        }

    }

?>