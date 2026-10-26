# Documentation To-Do List - ZubZet Framework (open items by priority)

The open items of `docs-todo.md` grouped by priority marker, each with its parent chain for context. Same item text, status tags and numbering rules as the main list.

- Priority markers on open items (filter with grep or search): `//urgent//`, `//important//`, `//should-do//`, `//nice-to-have//`, `//extra-effort//`, `//optional//` (highest to lowest). Parents carry no marker; ticked items need none. `docs-todo.by-priority.md` lists the open items grouped by marker.
- Priority meaning: `//urgent//` = the docs are wrong or misleading today, or a missing fact risks data loss, a security hole or a broken setup; `//important//` = core features every project uses; `//should-do//` = regular user-facing details and edge cases; `//nice-to-have//` = useful depth; `//extra-effort//` = worthwhile but costly (large reference sections, guides); `//optional//` = internal or rarely needed.

## //urgent// (90 items)

## //important// (524 items)

### 20. Open Pull Requests (not merged yet, all target `develop`)
- [ ] PR #199: feat(tasks): background tasks with a database-backed queue and worker container (WIP draft, 34 files, 4 commits, base develop (docs already written in PR)) [docs in PR]
  - [ ] Overview: Adds a database-backed background task system: `Tasks::dispatch()` queues work into `z_task`/`z_task_queue`, `queue:work` runs it in supervised worker processes, plus an owner-scoped status endpoint and three new settings. //important//
  - [ ] Dispatching tasks with `Tasks::dispatch()`
    - [ ] Signature: `dispatch(string $type, array $payload = [], ?string $name = null, string $queue = "default", int $delay = 0, ?int $userId = null): TaskRecord`; returns immediately, named arguments work. [docs in PR] //important//
  - [ ] Reading task state
    - [ ] `Tasks::find(int $id)`: returns a `TaskRecord` or `null`; no ownership check and ids are sequential, so own endpoints must compare `userId`. [docs in PR] //important//
  - [ ] Writing a task class
    - [ ] Location: `app/Tasks/` of the application or of a module, one class per file, class named exactly like the file, extending `ZubZet\Framework\Tasks\Task`. [docs in PR] //important//
    - [ ] `handle()`: the only required method; declared without a return type so implementations may add `: void` or `: array`. [docs in PR] //important//
  - [ ] Running workers with `queue:work`
    - [ ] Command: `php index.php queue:work`, registered in the console application with description "Run queued background tasks". [docs in PR] //important//
    - [ ] Nothing is processed unless a `queue:work` process is running; there is no web or cron trigger built in. [docs in PR] //important//

## //should-do// (927 items)

### 20. Open Pull Requests (not merged yet, all target `develop`)
- [ ] PR #199: feat(tasks): background tasks with a database-backed queue and worker container (WIP draft, 34 files, 4 commits, base develop (docs already written in PR)) [docs in PR]
  - [ ] Dispatching tasks with `Tasks::dispatch()`
    - [ ] `$type`: task class name or bare file name from `app/Tasks`; a namespaced name is cut to its last segment, so task classes must live in the global namespace. //should-do//
    - [ ] `$payload`: array stored as JSON in `z_task.payload` and exposed to the task as `$this->payload`; pass ids, not loaded models. [docs in PR] //should-do//
    - [ ] `$queue`: lane name, default `default`; a worker only consumes the one queue given by `--queue`. [docs in PR] //should-do//
    - [ ] `$delay`: seconds until the task becomes runnable (stored as `availableAt`); negative values are clamped to 0. [docs in PR] //should-do//
    - [ ] `$userId`: owner of the task; defaults to the logged-in user and is `null` in CLI or anonymous requests. [docs in PR] //should-do//
  - [ ] Reading task state
    - [ ] `Tasks::forUser(?int $userId = null, int $limit = 20)`: newest-first records of a user; defaults to the logged-in user, returns `[]` without one, limit minimum 1. [docs in PR] //should-do//
    - [ ] Polling pattern: check `isFinished()`/`hasFailed()`, report `progress`, read `result` when done. [docs in PR] //should-do//
  - [ ] `TaskRecord` (what userspace receives)
    - [ ] Properties: `id`, `type`, `name`, `status`, `payload` (array), `result` (?array), `error`, `progress`, `attempts`, `userId`, `created`, `startedAt`, `finishedAt`; timestamps are strings. [docs in PR] //should-do//
  - [ ] `TaskStatus` values
    - [ ] Statuses: `pending`, `running`, `done`, `failed`; `done` and `failed` are terminal. [docs in PR] //should-do//
  - [ ] Writing a task class
    - [ ] Return value: an array is stored as the task result (JSON); any other return value is stored as `null`. [docs in PR] //should-do//
    - [ ] Throwing: any `Throwable` marks the attempt failed and the message is recorded as `ExceptionClass: message`. [docs in PR] //should-do//
    - [ ] `$this->payload`: protected array copy of the dispatched payload. [docs in PR] //should-do//
    - [ ] `$this->progress(int $percent)`: protected; clamps to 0-100, updates `z_task.progress` and refreshes the worker's claim. [docs in PR] //should-do//
    - [ ] Name resolution
      - [ ] Lookup goes through the registry kind `tasks`: application first, then modules, no framework tasks; an application task shadows a module task of the same name. [docs in PR] //should-do//
  - [ ] Running workers with `queue:work`
    - [ ] Options
      - [ ] `--workers` / `-w`: number of worker processes, default `1`, minimum 1; the `-w` shortcut is not in the docs. [docs in PR] //should-do//
      - [ ] `--queue`: queue to consume, default `default`; one worker serves one queue, so other queues need their own worker. [docs in PR] //should-do//
    - [ ] Worker pool (`--workers` above 1)
      - [ ] Supervisor spawns N child processes running `queue:work --workers=1` via `proc_open` with `PHP_BINARY` and the original entry script; `proc_open` must not be disabled. [docs in PR] //should-do//
    - [ ] Worker recycling
      - [ ] `--max-jobs`, `--time-limit` and `--memory-limit` are checked between tasks only, so a running task is never interrupted. [docs in PR] //should-do//
      - [ ] A recycled worker exits and the supervisor starts a fresh one; a single worker (`--workers=1`) just ends, so the deployment needs `restart: always`. [docs in PR] //should-do//
    - [ ] Stopping workers
      - [ ] Supervisor drains on stop: closes each child's stdin, children finish the task in flight and exit; waits up to 60 s (fixed) and leaves still-busy workers to the reservation timeout. [docs in PR] //should-do//
      - [ ] Requires `ext-pcntl` to trap SIGTERM and SIGINT; without it the process is killed outright and in-flight tasks are recovered through `task_reservation_timeout`. [docs in PR] //should-do//
      - [ ] Container needs `init: true` (PID 1 gets no default signal handling) and `stop_grace_period` long enough for a task. [docs in PR] //should-do//
      - [ ] Apache-based images declare `STOPSIGNAL SIGWINCH`, which PHP ignores, so set `stop_signal: SIGTERM` on the worker service. [docs in PR] //should-do//
    - [ ] Cron alternative: `* * * * * cd /var/www && php index.php queue:work --time-limit=55` for hosts without long-lived processes, at up to a minute of latency. [docs in PR] //should-do//
  - [ ] Task lifecycle, retries and recovery
    - [ ] Normal run: claim, then `status = running`, `startedAt` set and `attempts` incremented, then `handle()`, then `done` with `progress = 100`, `result`, `finishedAt`, and the queue row deleted. [docs in PR] //should-do//
    - [ ] Failed attempt with attempts left: task goes back to `pending`, `progress` reset to 0, `startedAt` cleared, queue entry released with a delay of 10 s times the attempt number (fixed, not configurable). //should-do//
    - [ ] Final failure: queue row deleted, `status = failed`, `error` = `ExceptionClass: message`, `finishedAt` set; `error` stays empty while retries remain. [docs in PR] //should-do//
    - [ ] Delivery guarantee is at-least-once; tasks with `task_max_attempts` above 1 must be safe to run twice. [docs in PR] //should-do//
  - [ ] Built-in status endpoint `GET /_zubzet/task/{id}`
    - [ ] Authorization: only the logged-in owner is served; unknown id, unowned task, anonymous request or another user's task all answer 404 `{"error":"Task not found"}`. [docs in PR] //should-do//
    - [ ] Response: JSON from `toStatusArray()` (`id`, `type`, `name`, `status`, `progress`, `finished`, `created`, `startedAt`, `finishedAt`); never `payload`, `result` or `error`. [docs in PR] //should-do//
  - [ ] Settings (all optional, read from the application ini, workers need a restart to pick up changes)
    - [ ] `task_endpoint_enabled`: default `true`; `false` removes the `/_zubzet/task/{id}` route entirely (decided when routes register). [docs in PR] //should-do//
    - [ ] `task_max_attempts`: default `1` (retries are opt-in), values below 1 are treated as 1; attempts before a task is marked `failed`. [docs in PR] //should-do//
    - [ ] `task_reservation_timeout`: default `900` seconds, effective minimum 30 (smaller values are raised); age at which a claim counts as abandoned. [docs in PR] //should-do//
  - [ ] Database schema (new framework migration `2026-08-27_task.sql`, run by `db:migrate`)
    - [ ] Migration is picked up by existing installs on `db:migrate`, which now creates two more framework tables. [docs in PR] [changes existing behaviour] //should-do//
  - [ ] Database layer addition `Connection::$affectedRows`
    - [ ] New public `int $affectedRows` on the `Interaction` trait: rows changed by the last write or returned by the last read, set in `exec()`; only in the Changelog, not in docs. //should-do//
    - [ ] Gotcha: mysqli semantics count changed rows, not matched rows, so an UPDATE writing the current value reports 0; usable to tell whether a conditional update won. //should-do//
  - [ ] Deployment requirements
    - [ ] Run workers as an extra service from the same image as the application: `command: php index.php queue:work --workers=4 --time-limit=900`, `restart: always`, `init: true`, `stop_signal: SIGTERM`, `stop_grace_period: 45s`. [docs in PR] //should-do//
    - [ ] No broker or extra stateful service: the queue lives in the application's database; scale with `--workers` or more worker containers. [docs in PR] //should-do//
    - [ ] PHP: `ext-pcntl` is optional but needed for graceful stops, `proc_open` is needed for `--workers` above 1 (not stated in docs). //should-do//

## //nice-to-have// (722 items)

### 20. Open Pull Requests (not merged yet, all target `develop`)
- [ ] PR #199: feat(tasks): background tasks with a database-backed queue and worker container (WIP draft, 34 files, 4 commits, base develop (docs already written in PR)) [docs in PR]
  - [ ] Dispatching tasks with `Tasks::dispatch()`
    - [ ] `$name`: human-readable label, defaults to the task type. [docs in PR] //nice-to-have//
    - [ ] Unknown type: throws `\InvalidArgumentException` at dispatch ("Task 'X' does not exist. Expected a class named 'X' in app/Tasks."). [docs in PR] //nice-to-have//
    - [ ] Unencodable payload: throws `\InvalidArgumentException` ("payload cannot be stored: <json error>") instead of silently storing an empty payload. [docs in PR] //nice-to-have//
    - [ ] Not atomic: task row and queue row are two autocommits; if the queue insert fails the task is marked `failed` ("Could not be queued: ...") and the exception rethrown. //nice-to-have//
  - [ ] Reading task state
    - [ ] `Tasks::pending(string $queue = "default")`: counts unreserved queue rows, which also includes delayed tasks that are not yet due. [docs in PR] //nice-to-have//
    - [ ] Soft delete flag: `find()` and `forUser()` only see rows with `z_task.active = 1`; nothing in the PR sets `active` to 0. //nice-to-have//
  - [ ] `TaskRecord` (what userspace receives)
    - [ ] Helpers: `isFinished()`, `isRunning()`, `hasFailed()`. [docs in PR] //nice-to-have//
    - [ ] `toArray()`: every field for an application's own API responses, except `userId`. [docs in PR] //nice-to-have//
    - [ ] `toStatusArray()`: reduced view used by the framework endpoint (`id`, `type`, `name`, `status`, `progress`, `finished`, timestamps); not mentioned in the PR docs. //nice-to-have//
    - [ ] Stored JSON that does not decode to an array yields an empty `payload` and a `null` `result`. //nice-to-have//
  - [ ] `TaskStatus` values
    - [ ] Helpers: `TaskStatus::all()` and `TaskStatus::isFinished($status)`; class constants instead of an enum because PHP 8.0 is supported. //nice-to-have//
  - [ ] Writing a task class
    - [ ] `$this->record()`: public accessor for the current `TaskRecord`; not in the PR docs. //nice-to-have//
    - [ ] Name resolution
      - [ ] Type names must match `^[A-Za-z_][A-Za-z0-9_]*$`, so a crafted database row cannot address arbitrary paths. //nice-to-have//
      - [ ] Runtime errors become failed attempts: "does not exist in any app/Tasks directory", "does not declare a class named", "must extend ...Task". //nice-to-have//
  - [ ] Running workers with `queue:work`
    - [ ] Options
      - [ ] `--sleep`: seconds to wait on an empty queue, default `1`; a value below 1 still sleeps 1 second. [docs in PR] //nice-to-have//
      - [ ] `--max-jobs`: recycle a worker after this many tasks, default `0` (never). [docs in PR] //nice-to-have//
      - [ ] `--time-limit`: recycle a worker after this many seconds, default `0` (never). [docs in PR] //nice-to-have//
      - [ ] `--memory-limit`: recycle a worker at or above this many MB of `memory_get_usage(true)`, default `0` (never). [docs in PR] //nice-to-have//
      - [ ] `--once`: handle at most one task and exit; ignores `--workers`, and a database error while claiming aborts instead of being retried. [docs in PR] //nice-to-have//
    - [ ] Console output: "Worker started on queue X.", per task `done in Nms` / `failed after Nms` / `will retry (attempt N)`, and "Worker stopped after N tasks." //nice-to-have//
    - [ ] Worker pool (`--workers` above 1)
      - [ ] Why processes, not `pcntl_fork`: each child boots its own framework and database connection and no extension is needed. [docs in PR] //nice-to-have//
      - [ ] Supervision: checks every second and replaces children that recycled, crashed or failed to start ("Failed to start worker N, retrying."). [docs in PR] //nice-to-have//
      - [ ] Failure mode: exits with code 1 ("Could not determine the console entry point...") when the entry script cannot be resolved. //nice-to-have//
    - [ ] Claiming work
      - [ ] Exclusivity comes from the conditional update plus `affectedRows`; no `SELECT ... FOR UPDATE`, safe on Galera where a concurrent claim is refused as a certification conflict. [docs in PR] //nice-to-have//
    - [ ] Resilience of the worker loop
      - [ ] Database errors while claiming do not end the worker: retried with a 1 s to 30 s growing backoff, only the first 3 consecutive errors are printed. //nice-to-have//
      - [ ] Errors from a task or its bookkeeping never end the worker; a failed bookkeeping write is printed as "could not be processed". //nice-to-have//
      - [ ] A task that succeeded is not recorded as failed if writing the result hits a database error; the claim expires and is recovered. //nice-to-have//
    - [ ] Stopping workers
      - [ ] A single worker also traps SIGTERM and SIGINT with `ext-pcntl` and exits after its current task ("asked to stop"). //nice-to-have//
      - [ ] Liveness pipe: a supervised worker (`ZUBZET_TASK_SUPERVISED=1`) exits after its current task when its stdin hits EOF, which also covers a dead supervisor; hand-started workers are unaffected. [docs in PR] //nice-to-have//
  - [ ] Task lifecycle, retries and recovery
    - [ ] Task row deleted or deactivated mid-run: the run ends quietly, the queue entry is dropped and the worker prints "dropped orphaned queue entry". //nice-to-have//
    - [ ] Abandoned claim recovery
      - [ ] Every worker scans every 60 s (fixed), also while busy-queue never idles, for reservations older than `task_reservation_timeout`. [docs in PR] //nice-to-have//
      - [ ] Attempts left: entry returns to the queue as immediately available and the task to `pending`. [docs in PR] //nice-to-have//
      - [ ] No attempts left (default with `task_max_attempts = 1`): entry removed and task `failed` with "Abandoned: the worker running this task stopped before it finished." [docs in PR] //nice-to-have//
      - [ ] `progress()` refreshes `reservedAt`; a task running longer than the timeout without reporting progress may be picked up a second time. [docs in PR] //nice-to-have//
  - [ ] Built-in status endpoint `GET /_zubzet/task/{id}`
    - [ ] Route: registered in `DefaultRoutes` inside the `/_zubzet` group, `id` digits only, handled by `ZubZetController::task()`. [docs in PR] //nice-to-have//
    - [ ] Gotcha: `type` (class name) and `name` are included although the docs say "progress and timing only". //nice-to-have//
    - [ ] Applications wanting more data build their own endpoint on `Tasks::find()` and `toArray()`, as the e2e probe controller shows. [docs in PR] //nice-to-have//
  - [ ] Settings (all optional, read from the application ini, workers need a restart to pick up changes)
    - [ ] Path key `z_tasks` (default `app/Tasks/`) added to the framework path configuration. //nice-to-have//
    - [ ] `module:setup` treats all three `task_*` settings as framework-reserved, so a module ini can no longer introduce them. [changes existing behaviour] //nice-to-have//
  - [ ] Logging
    - [ ] New `LogEventType` constants `TASK_DONE` (info), `TASK_FAILED` (error) and `TASK_ABANDONED` (warning), written through `Logger::ZUBZET`; not in the PR docs. //nice-to-have//
    - [ ] Log context: `task` and `type` (done), plus `attempt` and `message` (failed), `task` and `attempts` (abandoned). `TASK_FAILED` is also used when recording a success fails. //nice-to-have//
  - [ ] Modules and registry
    - [ ] New registry kind `tasks` (`app/Tasks` in userspace and modules, nothing shipped by the framework), resolved userspace first then modules, with nested files reachable by bare name. [docs in PR] //nice-to-have//

## //extra-effort// (192 items)

### 20. Open Pull Requests (not merged yet, all target `develop`)
- [ ] PR #199: feat(tasks): background tasks with a database-backed queue and worker container (WIP draft, 34 files, 4 commits, base develop (docs already written in PR)) [docs in PR]
  - [ ] Database schema (new framework migration `2026-08-27_task.sql`, run by `db:migrate`)
    - [ ] `z_task` columns: `id` (INT auto-increment), `type` (VARCHAR 191), `name` (VARCHAR 255, default empty), `status` (VARCHAR 16, default `pending`). [docs in PR] //extra-effort//
    - [ ] `z_task` payload columns: `payload` and `result` (LONGTEXT JSON), `error` (TEXT), `progress` (TINYINT UNSIGNED, default 0), `attempts` (INT, default 0). [docs in PR] //extra-effort//
    - [ ] `z_task` ownership and time columns: `userId` (INT NULL), `active` (TINYINT(1), default 1), `created`, `startedAt`, `finishedAt` (TIMESTAMP). [docs in PR] //extra-effort//
    - [ ] `z_task` indexes: `status` and `userId`; rows are kept as history and nothing in the PR prunes finished tasks. //extra-effort//
    - [ ] `z_task_queue` columns: `id`, `taskId`, `queue` (VARCHAR 64, default `default`), `availableAt`, `reservedAt`, `reservedBy` (VARCHAR 64), `created`; holds outstanding work only, so an idle system polls an empty table. [docs in PR] //extra-effort//
    - [ ] `z_task_queue` indexes: UNIQUE `taskId` and composite `poll` (`queue`, `reservedAt`, `availableAt`). //extra-effort//

## //optional// (572 items)

### 20. Open Pull Requests (not merged yet, all target `develop`)
- [ ] PR #199: feat(tasks): background tasks with a database-backed queue and worker container (WIP draft, 34 files, 4 commits, base develop (docs already written in PR)) [docs in PR]
  - [ ] Running workers with `queue:work`
    - [ ] Worker pool (`--workers` above 1)
      - [ ] Children get the parent's options (except `--workers` and `--once`), the full environment via `getenv()` and `ZUBZET_TASK_SUPERVISED=1`. //optional//
    - [ ] Claiming work
      - [ ] Worker reads up to 10 runnable queue rows oldest first (unreserved, `availableAt` reached) and claims one with `UPDATE ... SET reservedAt, reservedBy WHERE id = ? AND reservedAt IS NULL`. //optional//
      - [ ] Lost acknowledgement: when a retried claim reports 0 rows the worker re-checks `reservedBy` and keeps the task if it already owns it. //optional//
      - [ ] Worker identity is `hostname:pid`, stored in `z_task_queue.reservedBy`. //optional//
  - [ ] Task lifecycle, retries and recovery
    - [ ] All transitions after the claim are conditional on still holding the reservation, so a worker with a stale view cannot release, delete or fail another worker's live run. //optional//
    - [ ] Abandoned claim recovery
      - [ ] Recovery statements re-test staleness, so two workers recovering at once cannot give one task to two runners. //optional//
  - [ ] Database schema (new framework migration `2026-08-27_task.sql`, run by `db:migrate`)
    - [ ] Migration uses `ADD INDEX IF NOT EXISTS` and `ADD UNIQUE KEY IF NOT EXISTS` (MariaDB syntax), no foreign keys. //optional//
  - [ ] Database layer addition `Connection::$affectedRows`
    - [ ] Included in the connection checkpoint (`#[IncludeInCheckpoint]`) so the slow-query logger does not clobber it. [internal] //optional//
  - [ ] Documentation shipped in the PR
    - [ ] New page `docs/advanced-features/background-tasks.md` plus `mkdocs.yml` nav entry under Advanced Features and a section in `docs/core-features/console-commands.md`. [docs in PR] //optional//
    - [ ] Changelog entries for background tasks (settings, workers, stop behaviour) and for `Connection::$affectedRows`. //optional//
  - [ ] Contributor-only changes
    - [ ] E2E stack: new `worker` compose service (same image, `init: true`, `stop_signal: SIGTERM`, `stop_grace_period: 45s`, `queue:work --workers=4 --time-limit=900`, waits until `php index.php list` works). [internal] //optional//
    - [ ] `COMPOSER_VENDOR_DIR` became a named volume `vendor` shared by application and worker; Dockerfile pre-creates `/var/www/vendor` and installs `pcntl`. [internal] //optional//
    - [ ] Coverage workflow gives the `worker` service the `XDEBUG_MODE` and `DEBUG_ZUBZET_COVERAGE_FRAMEWORK` environment. [internal] //optional//
    - [ ] E2E app doubles as worked example: `ReportTask`, `FailingTask`, `TaskProbeController`, `/tasks/*` routes and `tasks.cy.js` (14 specs). [internal] //optional//
    - [ ] All task SQL lives in the internal model `z_taskModel` (`IsInternalModel`); `Task::bind()`, `TaskRecord::fromRow()`, `Runner`, `Worker`, `WorkerPool`, `Signals` are `@internal`. [internal] //optional//
    - [ ] `docs/contributing/agents/working-with-agents.md` lists the `Tasks/` directory and `queue:work`. [internal] [docs in PR] //optional//
  - [ ] Open design questions raised in the PR description
    - [ ] `Tasks::cancel()` does not exist; whether it should is undecided. [WIP] //optional//
    - [ ] Default `task_max_attempts = 1` may still change. [WIP] //optional//
    - [ ] Whether the framework endpoint should serve unowned tasks behind a setting is undecided; currently they always 404. [WIP] //optional//