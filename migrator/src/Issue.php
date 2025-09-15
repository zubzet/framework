<?php

    namespace ZubZet\Tooling;

    class Issue {
        public function __construct(
            public string $file,
            public int $line,
            public array $messages = [],
        ) {}
    }

?>