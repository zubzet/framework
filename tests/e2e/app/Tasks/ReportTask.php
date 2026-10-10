<?php

    use ZubZet\Framework\Tasks\Task;

    /**
     * A representative long running task: it works in steps, reports progress
     * as it goes, and returns a result the application can read afterwards.
     */
    class ReportTask extends Task {

        public function handle() {
            $rows = (int) ($this->payload["rows"] ?? 10);
            $stepDelayMs = (int) ($this->payload["stepDelayMs"] ?? 50);

            $total = 0;
            for($row = 1; $row <= $rows; $row++) {
                $total += $row;

                // Reporting progress is what a frontend polls for, and it also
                // tells the worker this task is still alive.
                $this->progress((int) round($row / $rows * 100));

                usleep($stepDelayMs * 1000);
            }

            return [
                "rows" => $rows,
                "total" => $total,
                "workerPid" => getmypid(),
            ];
        }
    }

?>
