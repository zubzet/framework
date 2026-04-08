<?php

    class z_loggerModel extends z_model  {

        /**
         * Recursively converts arbitrary PHP values into JSON-safe primitives.
         */
        private function normalizeLogValue($value) {
            return match(true) {
                // Recurse into arrays, preserving keys
                is_array($value) => array_map(fn($item) => $this->normalizeLogValue($item), $value),

                // Format date objects as ISO-8601 instead of dumping internal properties
                $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),

                // Let objects define their own JSON shape, then normalize the result
                $value instanceof \JsonSerializable => $this->normalizeLogValue($value->jsonSerialize()),

                // Other objects: prefer __toString(), otherwise fall back to the class name
                is_object($value) => method_exists($value, '__toString') ? (string)$value : get_class($value),

                // Resources can't be json_encoded, so represent them by their type
                is_resource($value) => sprintf('resource(%s)', get_resource_type($value)),

                // Primitives (string, int, float, bool, null) pass through unchanged
                default => $value,
            };
        }

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
            $logRecord["environment"] = [
                "userId" => user()?->userId,
                "execUserId" => user()?->execUserId,
                "source" => request()->isCli() ? "cli" : "web",
            ];
        }

        public function log(array $logRecord) {
            // Prepare the log record data for storage,
            // ensuring it's JSON-serializable and includes environment context
            $dataValue = [
                "message" => $logRecord['message'],
                "context" => $this->normalizeLogValue($logRecord['context']),
                "level" => $logRecord['level'],
                "level_name" => $logRecord['level_name'],
                "channel" => $logRecord['channel'],
                "datetime" => $this->normalizeLogValue($logRecord['datetime']),
                "extra" => $this->normalizeLogValue($logRecord["extra"]),
            ];

            $this->appendEnvironment($dataValue);

            $query = $this->dbInsert("z_interaction_log", [
                "text" => $logRecord['message'],
                "value" => $this->encodeLogValue($dataValue),
                "userId" => user()?->userId,
                "userId_exec" => user()?->execUserId,
            ]);

            $this->exec($query);
        }

    }

?>