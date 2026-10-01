<?php

    namespace ZubZet\Framework\Tasks;

    /**
     * @internal
     *
     * Optional termination handling for long running console processes.
     *
     * Trapping SIGTERM needs ext-pcntl, which is not part of every PHP build,
     * so every call here is a no-op when it is missing. That degradation is
     * deliberate rather than hidden: without the extension a stopped worker is
     * killed mid-task, and the task is recovered through its reservation
     * timeout instead of being drained.
     */
    final class Signals {

        /** True when this build can trap termination signals at all. */
        public static function available(): bool {
            return function_exists("pcntl_signal") && function_exists("pcntl_async_signals");
        }

        /** Runs $handler when the process is asked to terminate. */
        public static function onShutdown(callable $handler): void {
            if(!self::available()) return;

            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, $handler);
            pcntl_signal(SIGINT, $handler);
        }

        /**
         * Delivers pending signals. Only needed where handlers must run at a
         * defined point; asynchronous delivery covers the rest.
         */
        public static function dispatch(): void {
            if(!function_exists("pcntl_signal_dispatch")) return;

            pcntl_signal_dispatch();
        }
    }

?>
