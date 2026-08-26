<?php

    use ZubZet\Framework\Tasks\Tasks;

    /**
     * Exercises the background task API exactly the way an application would:
     * dispatch from a request, then poll. Note that no method here touches the
     * database, the queue tables, or a worker.
     */
    class TaskProbeController extends z_controller {

        /** Starts a report and answers with the id a frontend polls with. */
        public function action_dispatch(Request $req, Response $res) {
            $task = Tasks::dispatch(ReportTask::class, [
                "rows" => (int) ($req->urlParameters["rows"] ?? 5),
                "stepDelayMs" => 50,
            ], name: "Monthly report");

            return $res->json([
                "id" => $task->id,
                "status" => $task->status,
                "name" => $task->name,
                "type" => $task->type,
            ]);
        }

        /** Dispatches a task that throws, to observe the failure path. */
        public function action_dispatchFailing(Request $req, Response $res) {
            $task = Tasks::dispatch(FailingTask::class, ["message" => "expected e2e failure"]);

            return $res->json(["id" => $task->id, "status" => $task->status]);
        }

        /**
         * Dispatches a failing task onto a named queue. Queues are lanes: the
         * running pool consumes "default" only, so work put here waits for a
         * worker started for this queue specifically.
         */
        public function action_dispatchToQueue(Request $req, Response $res) {
            $task = Tasks::dispatch(
                FailingTask::class,
                ["message" => "expected e2e failure"],
                queue: (string) $req->urlParameters["queue"],
            );

            return $res->json(["id" => $task->id, "status" => $task->status]);
        }

        /** Dispatches a task that is not runnable for a while. */
        public function action_dispatchDelayed(Request $req, Response $res) {
            $task = Tasks::dispatch(ReportTask::class, ["rows" => 1], delay: 3600);

            return $res->json(["id" => $task->id, "status" => $task->status]);
        }

        /**
         * A payload that cannot be stored as JSON is refused here, rather than
         * reaching the task as an empty array.
         */
        public function action_dispatchBadPayload(Request $req, Response $res) {
            try {
                Tasks::dispatch(ReportTask::class, ["label" => "caf\xE9"]);
            } catch(\InvalidArgumentException $e) {
                return $res->json(["error" => $e->getMessage()]);
            }

            return $res->json(["error" => null]);
        }

        /** Rejects an unknown task at dispatch time rather than in a worker. */
        public function action_dispatchUnknown(Request $req, Response $res) {
            try {
                Tasks::dispatch("NoSuchTask");
            } catch(\InvalidArgumentException $e) {
                return $res->json(["error" => $e->getMessage()]);
            }

            return $res->json(["error" => null]);
        }

        /**
         * An application's own status endpoint. Unlike the framework's, this
         * one exposes the full record, which is the application's call to make.
         */
        public function action_status(Request $req, Response $res) {
            $task = Tasks::find((int) $req->urlParameters["id"]);
            if(is_null($task)) {
                http_response_code(404);
                return $res->json(["error" => "unknown task"]);
            }

            return $res->json($task->toArray());
        }

        /** Number of tasks still waiting on a queue. */
        public function action_pending(Request $req, Response $res) {
            return $res->json(["pending" => Tasks::pending()]);
        }

        /** Dispatches a task owned by the logged in user, for the framework endpoint. */
        public function action_dispatchOwned(Request $req, Response $res) {
            $task = Tasks::dispatch(ReportTask::class, ["rows" => 2, "stepDelayMs" => 10]);

            return $res->json(["id" => $task->id, "userId" => $task->userId]);
        }

        /** The current user's most recent tasks. */
        public function action_mine(Request $req, Response $res) {
            $tasks = array_map(fn($task) => $task->toArray(), Tasks::forUser());

            return $res->json(["count" => count($tasks), "tasks" => $tasks]);
        }
    }

?>
