<?php

    namespace ZubZet\Framework\Logger\Method;

    use Monolog\Handler\AbstractProcessingHandler;

    class DatabaseLogger extends AbstractProcessingHandler {

        // Reentrancy guard: the z_logger insert itself runs through Connection::exec,
        // which can re-enter the logger if the insert trips the slow-query threshold.
        private static bool $isWriting = false;

        protected function write(array $record): void {
            if(self::$isWriting) return;

            // There might be logs before a database connection or in case of a connection failure
            // Which could still be caught using a different logger
            if(is_null(db(allowUnsetConnection: true))) return;

            self::$isWriting = true;
            try {
                // Log the record using the z_logger model, which handles normalization and encoding.
                // The insert can fail in legitimate states, e.g. mid-migration before z_interaction_log
                // has been created, and a logger must never crash it's caller.
                model("z_logger")->log($record);
            } catch(\Throwable $e) {
                // Discard: Logging is best-effort. A different handler (e.g. file) may still capture this.
            } finally {
                self::$isWriting = false;
            }
        }
    }

?>
