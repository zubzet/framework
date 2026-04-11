<?php 

    namespace ZubZet\Framework\Routing;

    // Dataclass to store pending actions for routes, groups, middlewares and afterwares
    class PendingAction {

        public $action;

        public function __construct(
            array|callable $action,
            public array $arguments = [],
        ) {
            $this->action = $action;
        }

        public function getAction(): array|callable {
            return $this->action;
        }
    }

?>