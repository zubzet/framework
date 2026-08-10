<?php

    namespace Module\Guestbook\Support;

    final class EntryFormatter {

        public static function format(string $author, string $message): string {
            return "{$author}: {$message}";
        }
    }

?>
