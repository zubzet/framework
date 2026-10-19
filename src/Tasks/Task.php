<?php

    namespace ZubZet\Framework\Tasks;

    /**
     * Base class for everything that runs in the background.
     *
     * A task lives in app/Tasks/ (application or module), is named like its
     * file, and implements handle(). Anything handle() returns as an array is
     * stored as the task's result; throwing marks the attempt as failed.
     *
     *     class ImportContactsTask extends Task {
     *         public function handle() {
     *             $rows = $this->readCsv($this->payload["file"]);
     *             $this->progress(50);
     *             return ["imported" => count($rows)];
     *         }
     *     }
     */
    abstract class Task {

        /** The array passed to Tasks::dispatch(). */
        protected array $payload = [];

        /** The persistent record of this run. */
        protected TaskRecord $record;

        /**
         * Runs the work. Return an array to store it as the task's result.
         *
         * Deliberately declared without a return type so an implementation may
         * narrow it (": void", ": array") as it sees fit.
         */
        abstract public function handle();

        /** The worker holding this task's claim, used to refresh it. */
        private ?string $workerId = null;

        /** @internal Called by the runner before handle(). */
        public function bind(TaskRecord $record, ?string $workerId = null): void {
            $this->record = $record;
            $this->payload = $record->payload;
            $this->workerId = $workerId;
        }

        /** The record of the currently running task. */
        public function record(): TaskRecord {
            return $this->record;
        }

        /**
         * Reports how far along the work is, between 0 and 100.
         *
         * This also refreshes the worker's claim on the task, so a long task
         * that reports progress is never mistaken for one whose worker died.
         * Tasks that run longer than task_reservation_timeout without calling
         * this may be picked up a second time.
         */
        protected function progress(int $percent): void {
            $percent = max(0, min(100, $percent));

            $this->record->progress = $percent;
            zubzet()->getModel("z_task")->updateProgress($this->record->id, $percent, $this->workerId);
        }
    }

?>
