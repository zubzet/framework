<?php

    namespace ZubZet\Tooling\Exceptions;

    class AbortRequiringUserAction extends \Exception {
        public function __construct(string $stepName) {
            parent::__construct(implode(PHP_EOL, [
                "The upgrade process was aborted and requires user action before continuing.",
                "Please read the output above for details and make the necessary changes.",
                "",
                "Skip this step by adding: --skip $stepName",
                "You can also use the shortcut: -s",
                "You may add multiple steps to skip using -s step1 -s step2",
            ]));
        }

    }

?>