<?php

    use ZubZet\Framework\Database\IsInternalModel;
    use ZubZet\Framework\Tasks\TaskStatus;

    /**
     * @internal
     *
     * Every statement the task system runs lives here. Userspace never touches
     * these tables: it goes through ZubZet\Framework\Tasks\Tasks and the Task
     * base class, which call into this model.
     */
    class z_taskModel extends z_model {

        use IsInternalModel;

        /** Inserts the task row plus its queue entry and returns the new task id. */
        public function createTask(
            string $type,
            string $name,
            array $payload,
            ?int $userId,
            string $queue,
            int $delaySeconds
        ): int {
            $this->exec(
                "INSERT INTO `z_task` (`type`, `name`, `payload`, `userId`) VALUES (?, ?, ?, ?)",
                "sssi",
                $type,
                $name,
                // Throwing beats storing the "false" that a failed encode
                // returns: that would bind as an empty column and reach the
                // task as an empty payload, silently running it with defaults.
                json_encode($payload, JSON_THROW_ON_ERROR),
                $userId,
            );

            $taskId = (int) $this->getInsertId();

            // Two statements, two autocommits. If the second one fails the task
            // would sit pending forever with nothing to run it, so the record is
            // closed off as failed rather than left as a promise nobody kept.
            try {
                // A delayed task is simply not available yet; the poll query
                // filters on availableAt, so no separate scheduler is needed.
                $this->exec(
                    "INSERT INTO `z_task_queue` (`taskId`, `queue`, `availableAt`)
                     VALUES (?, ?, CURRENT_TIMESTAMP() + INTERVAL ? SECOND)",
                    "isi",
                    $taskId,
                    $queue,
                    $delaySeconds,
                );
            } catch(\Throwable $e) {
                $this->markFailed($taskId, "Could not be queued: " . $e->getMessage());
                throw $e;
            }

            return $taskId;
        }

        /** @return array|null The raw z_task row, or null when the id is unknown. */
        public function findById(int $id): ?array {
            return $this->exec(
                "SELECT * FROM `z_task` WHERE `id` = ? AND `active` = 1",
                "i",
                $id,
            )->resultToLine();
        }

        /** @return array[] The user's most recent task rows, newest first. */
        public function findForUser(int $userId, int $limit): array {
            return $this->exec(
                "SELECT * FROM `z_task`
                 WHERE `userId` = ? AND `active` = 1
                 ORDER BY `id` DESC
                 LIMIT ?",
                "ii",
                $userId,
                $limit,
            )->resultToArray();
        }

        /**
         * Reserves one runnable task for this worker.
         *
         * Candidates are read first and then claimed one by one with a
         * conditional UPDATE. The condition is what makes the claim exclusive:
         * only the worker whose UPDATE actually changed a row owns the task, so
         * two workers racing on the same row cannot both proceed, with no
         * SELECT ... FOR UPDATE and no cross-node lock involved.
         *
         * @return array|null ["id" => queue row id, "taskId" => task id]
         */
        public function claim(string $queue, string $workerId): ?array {
            $candidates = $this->exec(
                "SELECT `id`, `taskId` FROM `z_task_queue`
                 WHERE `queue` = ?
                   AND `reservedAt` IS NULL
                   AND `availableAt` <= CURRENT_TIMESTAMP()
                 ORDER BY `id` ASC
                 LIMIT 10",
                "s",
                $queue,
            )->resultToArray();

            foreach($candidates as $candidate) {
                $connection = $this->exec(
                    "UPDATE `z_task_queue`
                     SET `reservedAt` = CURRENT_TIMESTAMP(), `reservedBy` = ?
                     WHERE `id` = ? AND `reservedAt` IS NULL",
                    "si",
                    $workerId,
                    $candidate["id"],
                );

                // Zero rows changed usually means another worker claimed this
                // row between our select and our update. It also happens when
                // the connection died after the server applied the claim but
                // before acknowledging it: the database layer then re-runs the
                // statement, which now matches nothing even though the
                // reservation is ours. Confirming the owner tells the two
                // apart, so a lost acknowledgement does not strand the task
                // until its reservation times out.
                if(1 !== $connection->affectedRows && !$this->holdsReservation($candidate["id"], $workerId)) {
                    continue;
                }

                return [
                    "id" => (int) $candidate["id"],
                    "taskId" => (int) $candidate["taskId"],
                    "workerId" => $workerId,
                ];
            }

            return null;
        }

        /** True when the given worker currently holds this queue row. */
        public function holdsReservation(int $queueId, string $workerId): bool {
            $row = $this->exec(
                "SELECT `id` FROM `z_task_queue` WHERE `id` = ? AND `reservedBy` = ?",
                "is",
                $queueId,
                $workerId,
            )->resultToLine();

            return !is_null($row);
        }

        /** Marks a claimed task as running and counts the attempt. */
        public function markRunning(int $taskId): void {
            $this->exec(
                "UPDATE `z_task`
                 SET `status` = ?, `startedAt` = CURRENT_TIMESTAMP(), `attempts` = `attempts` + 1
                 WHERE `id` = ?",
                "si",
                TaskStatus::RUNNING,
                $taskId,
            );
        }

        /** Terminal success state; the queue entry is dropped by the caller. */
        public function markDone(int $taskId, ?array $result): void {
            $this->exec(
                "UPDATE `z_task`
                 SET `status` = ?, `progress` = 100, `result` = ?, `finishedAt` = CURRENT_TIMESTAMP()
                 WHERE `id` = ?",
                "ssi",
                TaskStatus::DONE,
                is_null($result) ? null : json_encode($result, JSON_THROW_ON_ERROR),
                $taskId,
            );
        }

        /** Terminal failure state; the queue entry is dropped by the caller. */
        public function markFailed(int $taskId, string $error): void {
            $this->exec(
                "UPDATE `z_task`
                 SET `status` = ?, `error` = ?, `finishedAt` = CURRENT_TIMESTAMP()
                 WHERE `id` = ?",
                "ssi",
                TaskStatus::FAILED,
                $error,
                $taskId,
            );
        }

        /**
         * Returns a failed attempt to the queue after a backoff, for a retry.
         *
         * Scoped to the holder of the reservation, like every transition after
         * the claim: a worker acting on a stale view (its reservation timed out
         * and another worker took the task over) must not disturb the run that
         * is now in progress.
         *
         * @return bool Whether the release actually happened.
         */
        public function releaseForRetry(int $queueId, int $taskId, int $backoffSeconds, string $workerId): bool {
            $connection = $this->exec(
                "UPDATE `z_task_queue`
                 SET `reservedAt` = NULL,
                     `reservedBy` = NULL,
                     `availableAt` = CURRENT_TIMESTAMP() + INTERVAL ? SECOND
                 WHERE `id` = ? AND `reservedBy` = ?",
                "iis",
                $backoffSeconds,
                $queueId,
                $workerId,
            );

            if(1 !== $connection->affectedRows) return false;

            return $this->markPending($taskId);
        }

        /**
         * Releases a reservation whose worker stopped answering.
         *
         * The staleness test lives in the statement itself, so a reservation
         * that was refreshed or re-claimed in the meantime is left alone. Two
         * workers recovering at the same moment therefore cannot both release
         * it and hand the same task to two runners.
         *
         * @return bool Whether this caller performed the release.
         */
        public function releaseAbandoned(int $queueId, int $taskId, int $timeoutSeconds): bool {
            $connection = $this->exec(
                "UPDATE `z_task_queue`
                 SET `reservedAt` = NULL,
                     `reservedBy` = NULL,
                     `availableAt` = CURRENT_TIMESTAMP()
                 WHERE `id` = ?
                   AND `reservedAt` IS NOT NULL
                   AND `reservedAt` < CURRENT_TIMESTAMP() - INTERVAL ? SECOND",
                "ii",
                $queueId,
                $timeoutSeconds,
            );

            if(1 !== $connection->affectedRows) return false;

            return $this->markPending($taskId);
        }

        /**
         * Drops the entry of an abandoned task that has no attempts left, under
         * the same staleness test, so only one recoverer gives up on it.
         *
         * @return bool Whether this caller removed the entry.
         */
        public function removeAbandoned(int $queueId, int $timeoutSeconds): bool {
            $connection = $this->exec(
                "DELETE FROM `z_task_queue`
                 WHERE `id` = ?
                   AND `reservedAt` IS NOT NULL
                   AND `reservedAt` < CURRENT_TIMESTAMP() - INTERVAL ? SECOND",
                "ii",
                $queueId,
                $timeoutSeconds,
            );

            return 1 === $connection->affectedRows;
        }

        /** Puts a task back into the waiting state for a fresh attempt. */
        private function markPending(int $taskId): bool {
            // Progress belongs to the attempt that reported it; a fresh attempt
            // starts from zero rather than inheriting the failed one's number.
            $this->exec(
                "UPDATE `z_task` SET `status` = ?, `startedAt` = NULL, `progress` = 0 WHERE `id` = ?",
                "si",
                TaskStatus::PENDING,
                $taskId,
            );

            return true;
        }

        /**
         * Removes the queue entry of a task that reached a terminal state,
         * scoped to its holder for the same reason as releaseForRetry().
         *
         * @return bool Whether the entry was actually removed.
         */
        public function removeFromQueue(int $queueId, ?string $workerId = null): bool {
            $connection = is_null($workerId)
                ? $this->exec("DELETE FROM `z_task_queue` WHERE `id` = ?", "i", $queueId)
                : $this->exec(
                    "DELETE FROM `z_task_queue` WHERE `id` = ? AND `reservedBy` = ?",
                    "is",
                    $queueId,
                    $workerId,
                );

            return 1 === $connection->affectedRows;
        }

        /**
         * Stores progress and refreshes the reservation in one call, so a task
         * that reports progress can never be mistaken for an abandoned one.
         */
        public function updateProgress(int $taskId, int $percent, ?string $workerId = null): void {
            $this->exec(
                "UPDATE `z_task` SET `progress` = ? WHERE `id` = ?",
                "ii",
                $percent,
                $taskId,
            );

            // Only the holder refreshes the reservation, so a task whose claim
            // was taken over cannot keep the new holder's clock alive.
            if(is_null($workerId)) return;

            $this->exec(
                "UPDATE `z_task_queue`
                 SET `reservedAt` = CURRENT_TIMESTAMP()
                 WHERE `taskId` = ? AND `reservedBy` = ?",
                "is",
                $taskId,
                $workerId,
            );
        }

        /**
         * Reservations older than the timeout, meaning the worker holding them
         * died without releasing. Joined with the task so the caller can decide
         * between a retry and a permanent failure without a second query.
         *
         * @return array[] rows of id (queue), taskId, attempts
         */
        public function abandonedReservations(int $timeoutSeconds): array {
            return $this->exec(
                "SELECT `q`.`id`, `q`.`taskId`, `t`.`attempts`
                 FROM `z_task_queue` `q`
                 INNER JOIN `z_task` `t` ON `t`.`id` = `q`.`taskId`
                 WHERE `q`.`reservedAt` IS NOT NULL
                   AND `q`.`reservedAt` < CURRENT_TIMESTAMP() - INTERVAL ? SECOND",
                "i",
                $timeoutSeconds,
            )->resultToArray();
        }

        /** Number of tasks waiting to be picked up on a queue. */
        public function pendingCount(string $queue): int {
            $row = $this->exec(
                "SELECT COUNT(*) AS `count` FROM `z_task_queue`
                 WHERE `queue` = ? AND `reservedAt` IS NULL",
                "s",
                $queue,
            )->resultToLine();

            return (int) ($row["count"] ?? 0);
        }
    }

?>
