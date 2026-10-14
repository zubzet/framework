<?php

    namespace ZubZet\Tooling\Version;

    interface VersionInterface {
        public function upgrade(): bool;
    }

?>