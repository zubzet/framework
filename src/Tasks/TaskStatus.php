<?php

    namespace ZubZet\Framework\Tasks;

    /**
     * The states a task moves through. Class constants rather than an enum,
     * because the framework supports PHP 8.0.
     */
    final class TaskStatus {

        /** Dispatched and waiting for a worker. */
        public const PENDING = "pending";

        /** Claimed by a worker and currently executing. */
        public const RUNNING = "running";

        /** Finished successfully. Terminal. */
        public const DONE = "done";

        /** Gave up after the configured attempts. Terminal. */
        public const FAILED = "failed";

        /** @return string[] Every state a task can be in. */
        public static function all(): array {
            return [self::PENDING, self::RUNNING, self::DONE, self::FAILED];
        }

        /** True once the task will not change state again. */
        public static function isFinished(string $status): bool {
            return in_array($status, [self::DONE, self::FAILED], true);
        }
    }

?>
