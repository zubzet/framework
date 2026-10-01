# Background Tasks

Some work does not fit in a request: importing an uploaded file, generating a
report, calling a slow third party. A request that waits for it holds a PHP
worker, risks a gateway timeout, and leaves the user staring at a spinner with
no way to recover if the tab closes.

ZubZet solves this by writing the work down and letting a separate process do
it. The request stays fast, the frontend gets an id to poll, and the work
survives the request that started it.

```php
use ZubZet\Framework\Tasks\Tasks;

// In a controller: returns immediately.
$task = Tasks::dispatch(ImportContactsTask::class, ["file" => $path]);
return $res->json(["taskId" => $task->id]);
```

## Writing a task

Tasks live in `app/Tasks/`, one class per file named like the file, extending
`Task`. The only required method is `handle()`.

```php
<?php

    use ZubZet\Framework\Tasks\Task;

    class ImportContactsTask extends Task {

        public function handle() {
            $rows = $this->readCsv($this->payload["file"]);

            foreach($rows as $index => $row) {
                $this->import($row);
                $this->progress((int) round($index / count($rows) * 100));
            }

            return ["imported" => count($rows)];
        }
    }

?>
```

- `$this->payload` is the array passed to `dispatch()`. It is stored as JSON, so
  it must be JSON encodable: pass an id, not a loaded model.
- Returning an array stores it as the task's result.
- Throwing marks the attempt as failed. The message is recorded on the task.
- `$this->progress(int $percent)` records how far along the work is. It also
  refreshes the worker's claim, so a task that reports progress is never
  mistaken for one whose worker died.

Modules ship tasks the same way, in their own `app/Tasks/`. An application task
shadows a module task of the same name, like controllers and models do.

## Dispatching and polling

`Tasks` is the whole application-facing API. Nothing in it exposes the
database.

| Call | Returns |
| ---- | ------- |
| `Tasks::dispatch($type, $payload, $name, $queue, $delay, $userId)` | The queued `TaskRecord` |
| `Tasks::find($id)` | The current `TaskRecord`, or `null` |
| `Tasks::forUser($userId, $limit)` | The user's recent `TaskRecord`s |
| `Tasks::pending($queue)` | How many tasks are waiting |

A `TaskRecord` carries `id`, `type`, `name`, `status`, `progress`, `attempts`,
`payload`, `result`, `error`, `userId` and the `created` / `startedAt` /
`finishedAt` timestamps, plus `isFinished()`, `isRunning()`, `hasFailed()` and
`toArray()`.

Status moves `pending` to `running`, then to `done` or `failed`.

```php
$task = Tasks::find($id);

// find() returns null for an unknown id, and task ids are sequential, so an
// endpoint that serves them has to check who is asking.
if(is_null($task) || $task->userId !== user()->userId) {
    http_response_code(404);
    return $res->json(["error" => "Task not found"]);
}

if($task->hasFailed()) return $res->json(["error" => "Import failed"]);
if(!$task->isFinished()) return $res->json(["progress" => $task->progress]);

return $res->json(["imported" => $task->result["imported"]]);
```

The payload is stored as JSON, so it has to be encodable: `dispatch()` rejects
anything that is not, in the request that built it.

### Dispatch options

```php
Tasks::dispatch(
    ReportTask::class,
    ["month" => "2026-08"],
    name: "August report",   // human readable label, defaults to the type
    queue: "reports",        // lane, so slow work cannot starve quick work
    delay: 300,              // seconds before the task becomes runnable
);
```

The owner defaults to the logged in user, which is what the framework's status
endpoint scopes on. Pass `userId` explicitly to dispatch on someone's behalf,
for example from a console command.

An unknown task type is rejected by `dispatch()` itself, so a typo surfaces in
the request that made it rather than in a worker minutes later.

### The built in status endpoint

`GET /_zubzet/task/{id}` answers with the task's progress and timing. It is
deliberately narrow: it serves a task only to the user who owns it, and it
never returns `payload`, `result` or `error`, since those carry application
data. Unowned tasks are not readable through it at all.

Applications that want to expose more build their own endpoint on
`Tasks::find()`, exactly as in the polling example above. Set
`task_endpoint_enabled = false` to remove the framework's endpoint entirely.

## Running the workers

Work only happens while `queue:work` is running.

```bash
php index.php queue:work --workers=4
```

With more than one worker the process supervises that many worker processes and
replaces them as they recycle or die. Workers are separate processes rather
than threads or forks, so each has its own database connection and a crash in
one cannot affect another.

| Option | Default | Meaning |
| ------ | ------- | ------- |
| `--workers` | `1` | How many worker processes to run |
| `--queue` | `default` | Which queue to consume |
| `--sleep` | `1` | Seconds to wait when no task is available |
| `--max-jobs` | `0` | Recycle a worker after this many tasks |
| `--time-limit` | `0` | Recycle a worker after this many seconds |
| `--memory-limit` | `0` | Recycle a worker above this many MB |
| `--once` | off | Run at most one task, then exit |

Recycling exists because a long lived PHP process accumulates whatever the
application leaks. A recycled worker exits between tasks and the supervisor
starts a fresh one, so memory returns to its baseline without dropping work.

### In production

Run the workers as their own service, from the same image as the application:

```yaml
  worker:
    image: your-application:latest
    command: php index.php queue:work --workers=4 --time-limit=900
    restart: always
    init: true                 # so the supervisor is signalled on stop
    stop_signal: SIGTERM       # web images often declare a different one
    stop_grace_period: 45s     # time for a worker to finish its task
```

That is the entire infrastructure requirement. There is no broker to operate
and make highly available: the queue lives in the database the application
already depends on. Scale by raising `--workers`, or by running more worker
containers; workers coordinate purely through the database, so they need no
knowledge of each other.

On hosts where a long lived process is not an option, a cron entry works too,
at the cost of up to a minute of latency:

```
* * * * * cd /var/www && php index.php queue:work --time-limit=55
```

### Stopping workers

Stopping the supervisor (`docker stop`, Ctrl+C) makes every worker finish the
task it is running and then exit. The supervisor holds each worker's standard
input open, and closing it is the signal to drain.

Two things have to be true for that, and both are easy to get wrong:

- **`ext-pcntl` must be available.** Trapping the stop signal is the one part
  that no core PHP function can do. Without the extension the process is killed
  outright, tasks in flight are interrupted, and they are recovered through
  their reservation timeout instead. Nothing is lost, but the stop is abrupt.
- **The supervisor must actually receive the signal.** A container's PID 1 does
  not get default signal handling from the kernel, so run the worker container
  with an init (`init: true` in Compose) and give it a `stop_grace_period` long
  enough for a task to finish. Watch the stop signal too: reusing the web
  image means inheriting its `STOPSIGNAL`, and an Apache image declares
  `SIGWINCH`, which PHP ignores. Set `stop_signal: SIGTERM` on the worker
  service, or stopping it kills the workers instead of draining them.

Workers also notice a supervisor that died rather than stopped, through the same
closed pipe, and exit after their current task.

## Reliability

The system is at-least-once, and the two tables make the guarantees explicit.

`z_task` is the permanent record: one row per dispatched task, kept after it
finishes, which is what a frontend polls and what remains as history.
`z_task_queue` holds outstanding work only, so an idle installation polls an
empty table and a row disappears the moment its task reaches a final state.

A worker claims a task with a conditional update on that queue row, so exactly
one worker can own a task even when several poll simultaneously. This holds on
a Galera cluster: a concurrent claim on another node is refused as a
certification conflict rather than silently succeeding.

If a worker dies mid-task, its claim is left behind. Any worker notices claims
older than `task_reservation_timeout` and either returns them to the queue or
marks them failed, depending on how many attempts the task has left. This is
also why a task longer than that timeout should report progress: doing so
refreshes the claim.

Retries are off by default (`task_max_attempts = 1`), because re-running a
partially applied import can be worse than reporting the failure. Raise it for
tasks that are safe to repeat, and write those tasks so that running them twice
is harmless.

## Settings

| Setting | Default | Meaning |
| ------- | ------- | ------- |
| `task_endpoint_enabled` | `true` | Serve `GET /_zubzet/task/{id}` |
| `task_max_attempts` | `1` | Attempts before a task is marked failed |
| `task_reservation_timeout` | `900` | Seconds before a claim counts as abandoned |

Workers read settings at start, so changing any of these takes effect when the
workers are restarted.

## Schema

`db:migrate` creates both tables.

`z_task`: `id`, `type`, `name`, `status`, `payload`, `result`, `error`,
`progress`, `attempts`, `userId`, `active`, `created`, `startedAt`,
`finishedAt`.

`z_task_queue`: `id`, `taskId`, `queue`, `availableAt`, `reservedAt`,
`reservedBy`, `created`.
