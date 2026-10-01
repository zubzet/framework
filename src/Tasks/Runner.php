<?php

    namespace ZubZet\Framework\Tasks;

    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Logger\LogEventType;

    /**
     * @internal
     *
     * Executes one claimed task and moves it to its next state. Every exit path
     * either drops the queue entry (terminal) or releases it (retry), so a task
     * can never stay reserved by a worker that has moved on.
     */
    final class Runner {

        /** Seconds added before each retry, multiplied by the attempt number. */
        private const RETRY_BACKOFF_STEP_SECONDS = 10;

        /**
         * @param array $claim ["id" => queue row id, "taskId" => task id]
         * @return TaskRecord|null The finished record, or null when the row vanished.
         */
        public function run(array $claim): ?TaskRecord {
            $model = zubzet()->getModel("z_task");

            if(is_null($model->findById($claim["taskId"]))) {
                // The task row was deleted while queued; drop the orphan entry.
                $model->removeFromQueue($claim["id"], $claim["workerId"] ?? null);
                return null;
            }

            $model->markRunning($claim["taskId"]);

            $record = $this->reload($claim);
            if(is_null($record)) return null;

            try {
                $result = $this->execute($record, $claim);
            } catch(\Throwable $e) {
                $this->fail($claim, $record, $e);
                return $this->reload($claim);
            }

            // The work itself succeeded. A database problem while recording
            // that must not be reported as the task having failed: leaving the
            // claim to expire and be recovered is the honest outcome, and the
            // one the retry policy already covers.
            try {
                $model->markDone($record->id, $result);
                $model->removeFromQueue($claim["id"], $claim["workerId"] ?? null);

                logger(Logger::ZUBZET)->info(LogEventType::TASK_DONE, [
                    "task" => $record->id,
                    "type" => $record->type,
                ]);
            } catch(\Throwable $e) {
                logger(Logger::ZUBZET)->error(LogEventType::TASK_FAILED, [
                    "task" => $record->id,
                    "type" => $record->type,
                    "message" => "Task succeeded but its result could not be recorded: " . $e->getMessage(),
                ]);
            }

            return $this->reload($claim);
        }

        /**
         * Re-reads the task after a state change. The row is legitimately
         * nullable: an application may delete or deactivate a task while it
         * runs, and that must end the run quietly rather than fail on a type.
         */
        private function reload(array $claim): ?TaskRecord {
            $model = zubzet()->getModel("z_task");

            $row = $model->findById($claim["taskId"]);
            if(is_null($row)) {
                $model->removeFromQueue($claim["id"], $claim["workerId"] ?? null);
                return null;
            }

            return TaskRecord::fromRow($row);
        }

        /** Instantiates the task, runs it, and normalizes what it returned. */
        private function execute(TaskRecord $record, array $claim): ?array {
            $task = TaskResolver::instantiate($record->type);
            $task->bind($record, $claim["workerId"] ?? null);

            $result = $task->handle();

            return is_array($result) ? $result : null;
        }

        /**
         * Retries while attempts remain, otherwise records the failure. The
         * attempt was already counted by markRunning(), so the comparison here
         * is against what has been spent.
         */
        private function fail(array $claim, TaskRecord $record, \Throwable $e): void {
            $model = zubzet()->getModel("z_task");
            $maxAttempts = max(1, (int) config("task_max_attempts", default: 1));

            $message = get_class($e) . ": " . $e->getMessage();

            logger(Logger::ZUBZET)->error(LogEventType::TASK_FAILED, [
                "task" => $record->id,
                "type" => $record->type,
                "attempt" => $record->attempts,
                "message" => $message,
            ]);

            $workerId = $claim["workerId"] ?? "";

            if($record->attempts < $maxAttempts) {
                $model->releaseForRetry(
                    $claim["id"],
                    $record->id,
                    self::RETRY_BACKOFF_STEP_SECONDS * $record->attempts,
                    $workerId,
                );
                return;
            }

            // Only the worker that still holds the entry declares the task
            // failed. Losing the entry means the task was taken over, and that
            // run owns the outcome now.
            if(!$model->removeFromQueue($claim["id"], $workerId)) return;

            $model->markFailed($record->id, $message);
        }

        /**
         * Returns reservations held by workers that died to the queue, so their
         * work is not stuck forever. Called periodically by every worker, which
         * needs no leader: releasing an already released row is a no-op.
         *
         * @return int Number of reservations recovered.
         */
        public function recoverAbandoned(): int {
            $model = zubzet()->getModel("z_task");
            $timeout = max(30, (int) config("task_reservation_timeout", default: 900));
            $maxAttempts = max(1, (int) config("task_max_attempts", default: 1));

            $abandoned = $model->abandonedReservations($timeout);

            foreach($abandoned as $entry) {
                $taskId = (int) $entry["taskId"];
                $attempts = (int) $entry["attempts"];

                logger(Logger::ZUBZET)->warning(LogEventType::TASK_ABANDONED, [
                    "task" => $taskId,
                    "attempts" => $attempts,
                ]);

                if($attempts < $maxAttempts) {
                    $model->releaseAbandoned((int) $entry["id"], $taskId, $timeout);
                    continue;
                }

                // Both statements re-test the staleness, so a reservation that
                // was refreshed or re-claimed since the scan is left alone and
                // only one recoverer ever gives up on a task.
                if(!$model->removeAbandoned((int) $entry["id"], $timeout)) continue;

                $model->markFailed($taskId, "Abandoned: the worker running this task stopped before it finished.");
            }

            return count($abandoned);
        }
    }

?>
