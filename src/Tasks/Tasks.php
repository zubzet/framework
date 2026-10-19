<?php

    namespace ZubZet\Framework\Tasks;

    /**
     * The task system's entire application-facing API.
     *
     * Dispatching returns immediately: the request only writes the task row and
     * its queue entry, and a worker process picks the work up. Nothing here
     * exposes the database, so an application never writes a query to start
     * background work or to poll it.
     *
     *     $task = Tasks::dispatch(ImportContactsTask::class, ["file" => $path]);
     *     return $res->json(["taskId" => $task->id]);
     *
     *     $task = Tasks::find($id);
     *     $task->status;   // pending | running | done | failed
     */
    final class Tasks {

        /**
         * Queues a task and returns its record, including the id a frontend
         * polls with.
         *
         * @param string $type Task class name, or the bare file name in app/Tasks.
         * @param array $payload Data handed to the task; must be JSON encodable.
         * @param string|null $name Human readable label, defaults to the type.
         * @param string $queue Queue name, for routing work to specific workers.
         * @param int $delay Seconds to wait before the task becomes runnable.
         * @param int|null $userId Owner; defaults to the logged in user, if any.
         */
        public static function dispatch(
            string $type,
            array $payload = [],
            ?string $name = null,
            string $queue = "default",
            int $delay = 0,
            ?int $userId = null
        ): TaskRecord {
            $type = self::shortName($type);

            // Fail at dispatch time rather than inside a worker minutes later:
            // an unresolvable task is a typo, not a runtime condition.
            if(is_null(TaskResolver::locate($type))) {
                throw new \InvalidArgumentException(
                    "Task '$type' does not exist. Expected a class named '$type' in app/Tasks."
                );
            }

            // The payload is stored as JSON. Rejecting it here reports the
            // problem to the code that built it; storing an unencodable value
            // would hand the task an empty payload and fail confusingly, in a
            // worker, much later.
            if(false === json_encode($payload)) {
                throw new \InvalidArgumentException(
                    "Task '$type' payload cannot be stored: " . json_last_error_msg() . "."
                );
            }

            $model = zubzet()->getModel("z_task");
            $taskId = $model->createTask(
                $type,
                $name ?? $type,
                $payload,
                $userId ?? self::currentUserId(),
                $queue,
                max(0, $delay),
            );

            return TaskRecord::fromRow($model->findById($taskId));
        }

        /** The current state of one task, or null when the id is unknown. */
        public static function find(int $id): ?TaskRecord {
            $row = zubzet()->getModel("z_task")->findById($id);
            if(is_null($row)) return null;

            return TaskRecord::fromRow($row);
        }

        /**
         * The most recent tasks owned by a user, newest first.
         *
         * @return TaskRecord[]
         */
        public static function forUser(?int $userId = null, int $limit = 20): array {
            $userId = $userId ?? self::currentUserId();
            if(is_null($userId)) return [];

            $rows = zubzet()->getModel("z_task")->findForUser($userId, max(1, $limit));
            return array_map(fn(array $row) => TaskRecord::fromRow($row), $rows);
        }

        /** How many tasks are waiting to be picked up on a queue. */
        public static function pending(string $queue = "default"): int {
            return zubzet()->getModel("z_task")->pendingCount($queue);
        }

        /**
         * Accepts both ImportTask::class and "ImportTask". Task files declare a
         * global class named like the file, so a namespaced argument is reduced
         * to its last segment.
         */
        private static function shortName(string $type): string {
            $segments = explode("\\", $type);
            return (string) array_pop($segments);
        }

        /** The logged in user's id, or null in CLI and anonymous requests. */
        private static function currentUserId(): ?int {
            $user = user();
            if(!$user || !$user->isLoggedIn) return null;
            if(is_null($user->userId)) return null;

            return (int) $user->userId;
        }
    }

?>
