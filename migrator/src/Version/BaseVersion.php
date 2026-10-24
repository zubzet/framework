<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\Upgrade;
    use ZubZet\Tooling\ReleaseState;

    class BaseVersion {
        public string $stability = ReleaseState::Development;

        public function __construct(
            public Upgrade $upgrade,
            public string $semanticVersion,
        ) {}
    }

?>