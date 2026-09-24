<?php

    use ZubZet\Framework\Tasks\Task;

    /** Fails on purpose, so the failure and retry paths are covered. */
    class FailingTask extends Task {

        public function handle() {
            throw new \RuntimeException($this->payload["message"] ?? "This task always fails");
        }
    }

?>
