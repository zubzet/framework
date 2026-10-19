<?php

    use ZubZet\Framework\Tasks\Tasks;

    class ZubZetController {

        /**
         * Progress of one background task, for a frontend that is polling after
         * dispatching work.
         *
         * Scoped to the task's owner, and limited to progress and timing:
         * payload, result and error can carry application internals, so an
         * application that wants to expose those builds its own endpoint on
         * Tasks::find(). Unowned tasks (dispatched from the CLI or by an
         * anonymous request) are not readable here at all.
         */
        public function task(Request $req, Response $res) {
            $task = Tasks::find((int) $req->urlParameters["id"]);

            $user = user();
            $userId = ($user && $user->isLoggedIn) ? $user->userId : null;

            if(is_null($task) || is_null($task->userId) || $task->userId !== $userId) {
                http_response_code(404);
                return $res->json(["error" => "Task not found"]);
            }

            return $res->json($task->toStatusArray());
        }

        public function health(Request $req, Response $res) {
            // The buffer discards connection warnings PHP 8.0 emits instead of throwing
            ob_start();
            try {
                db()->exec("SELECT 1");
                $healthy = true;
            } catch(\Throwable) {
                $healthy = false;
            }
            ob_end_clean();

            if(!$healthy) http_response_code(503);
            return $res->json(["status" => $healthy ? "healthy" : "unhealthy"]);
        }

    }

?>
