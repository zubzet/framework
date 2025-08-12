<?php 

namespace ZubZet\Framework\Routing;

class PendingRoute extends PendingRoutingState {

    public function __construct(
        private string $method,
        private string $endpoint,
        private array $action,
    ) {}

    public function __destruct() {
        Route::performRoute(
            $this->method,
            $this->endpoint,
            $this->action,
            ...$this->middleware
        );
    }
}

?>