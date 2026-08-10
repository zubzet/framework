<?php

    // Lives one directory deep on purpose: retrieved via the bare name
    // "GuestbookStats", proving the recursive model lookup spans module roots.
    class GuestbookStatsModel extends z_model {

        public function countEntries() {
            $query = $this->dbSelect("COUNT(*) AS entryCount", "guestbook_entries");

            return $this->exec($query)->resultToLine()["entryCount"];
        }
    }

?>
