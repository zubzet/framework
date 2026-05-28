<?php

    use ZubZet\Framework\Database\IsInternalModel;

    /**
     * @internal
     */
    class z_loggerModel extends z_model  {

        use IsInternalModel;

        /**
         * Encodes a normalized log payload as JSON, with a safe fallback on failure.
         */
        private function encodeLogValue(array $dataValue): string {
            try {
                // Readable unicode/slashes, tolerate broken UTF-8, throw on any other error
                return json_encode($dataValue,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_INVALID_UTF8_SUBSTITUTE
                    | JSON_THROW_ON_ERROR
                );
            } catch(\JsonException $e) {
                // Fallback: keep at least the message and the encoding error itself
                return json_encode([
                    'message' => isset($dataValue['message']) ? (string)$dataValue['message'] : 'Log encoding failed',
                    'encoding_error' => $e->getMessage(),
                ]);
            }
        }

        public function appendEnvironment(&$logRecord) {
            // Append contextual information about the environment to the log record
            $logRecord["extra"] = array_merge($logRecord["extra"] ?? [], [
                "userId" => user()?->userId,
                "execUserId" => user()?->execUserId,
                "source" => request()->isCli() ? "cli" : "web",
            ]);
        }

        public function log(array $logRecord) {
            $this->appendEnvironment($logRecord);
            unset($logRecord['formatted']);

            $query = $this->dbInsert("z_interaction_log", [
                "text" => $logRecord['message'],
                "value" => $this->encodeLogValue($logRecord),
                "userId" => user()?->userId,
                "userId_exec" => user()?->execUserId,
            ]);

            $this->exec($query);
        }

    }

?>