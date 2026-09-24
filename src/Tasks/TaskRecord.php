<?php

    namespace ZubZet\Framework\Tasks;

    /**
     * One row of z_task as a plain object. This is what userspace receives from
     * Tasks::dispatch() and Tasks::find(), so it is the whole read surface of
     * the task system: no query builder, no connection, no table names.
     */
    final class TaskRecord {

        public int $id;
        public string $type;
        public string $name;
        public string $status;

        /** The array handed to Tasks::dispatch(). */
        public array $payload = [];

        /** Whatever the task returned, or null. */
        public ?array $result = null;

        /** Failure message of the final attempt, or null. */
        public ?string $error = null;

        /** 0 to 100, as reported by the task itself. */
        public int $progress = 0;

        public int $attempts = 0;
        public ?int $userId = null;

        public ?string $created = null;
        public ?string $startedAt = null;
        public ?string $finishedAt = null;

        /** @internal Builds a record from a raw database row. */
        public static function fromRow(array $row): self {
            $record = new self();

            $record->id = (int) $row["id"];
            $record->type = (string) $row["type"];
            $record->name = (string) $row["name"];
            $record->status = (string) $row["status"];
            $record->progress = (int) $row["progress"];
            $record->attempts = (int) $row["attempts"];
            $record->userId = is_null($row["userId"]) ? null : (int) $row["userId"];
            $record->error = $row["error"];
            $record->created = $row["created"];
            $record->startedAt = $row["startedAt"];
            $record->finishedAt = $row["finishedAt"];

            $record->payload = self::decode($row["payload"]) ?? [];
            $record->result = self::decode($row["result"]);

            return $record;
        }

        private static function decode(?string $json): ?array {
            if(is_null($json) || "" === $json) return null;

            $decoded = json_decode($json, true);
            if(!is_array($decoded)) return null;

            return $decoded;
        }

        /** True once the task will not change state again. */
        public function isFinished(): bool {
            return TaskStatus::isFinished($this->status);
        }

        public function isRunning(): bool {
            return TaskStatus::RUNNING === $this->status;
        }

        public function hasFailed(): bool {
            return TaskStatus::FAILED === $this->status;
        }

        /** Everything about the task, for an application's own API responses. */
        public function toArray(): array {
            return [
                "id" => $this->id,
                "type" => $this->type,
                "name" => $this->name,
                "status" => $this->status,
                "progress" => $this->progress,
                "attempts" => $this->attempts,
                "payload" => $this->payload,
                "result" => $this->result,
                "error" => $this->error,
                "created" => $this->created,
                "startedAt" => $this->startedAt,
                "finishedAt" => $this->finishedAt,
            ];
        }

        /**
         * The subset the framework's own status endpoint returns. Progress and
         * timing only: payload, result and error can carry internals, so
         * exposing those stays an application decision made with toArray().
         */
        public function toStatusArray(): array {
            return [
                "id" => $this->id,
                "type" => $this->type,
                "name" => $this->name,
                "status" => $this->status,
                "progress" => $this->progress,
                "finished" => $this->isFinished(),
                "created" => $this->created,
                "startedAt" => $this->startedAt,
                "finishedAt" => $this->finishedAt,
            ];
        }
    }

?>
