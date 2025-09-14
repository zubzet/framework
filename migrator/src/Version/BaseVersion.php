<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\Upgrade;

    class BaseVersion {
        public function __construct(
            public Upgrade $upgrade,
            public string $semanticVersion,
        ) {}
    }

?>