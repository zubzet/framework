<?php

    namespace ZubZet\Framework\Database;

    /** One failed statement attempt: what execWithRecovery() classifies on, plus the exception to throw. */
    class StatementFailure {

        public \Exception $exception;

        private function __construct(
            string $prefix,
            public int $errorCode,
            public ?string $sqlState,
            string $message,
            string $query,
            ?\mysqli_sql_exception $error,
        ) {
            $this->exception = new \Exception($prefix . ": " . $message . "\nQuery: " . $query, 0, $error);
        }

        /** Reaching the server or preparing the statement failed. */
        public static function preparing(int $errorCode, ?string $sqlState, string $message, string $query, ?\mysqli_sql_exception $error = null): self {
            return new self("SQL Error", $errorCode, $sqlState, $message, $query, $error);
        }

        /** Running the prepared statement failed. */
        public static function executing(int $errorCode, ?string $sqlState, string $message, string $query, ?\mysqli_sql_exception $error = null): self {
            return new self("SQL Execution Error", $errorCode, $sqlState, $message, $query, $error);
        }

    }

?>
