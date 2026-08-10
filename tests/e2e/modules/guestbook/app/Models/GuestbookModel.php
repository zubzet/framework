<?php

    class GuestbookModel extends z_model {

        public function getEntries() {
            $query = $this->dbSelect(["id", "author", "message"], "guestbook_entries")
                            ->orderAsc("id");

            return $this->exec($query)->resultToArray();
        }

        public function addEntry($author, $message) {
            $query = $this->dbInsert("guestbook_entries", [
                "author" => $author,
                "message" => $message,
            ]);

            $this->exec($query);
        }
    }

?>
