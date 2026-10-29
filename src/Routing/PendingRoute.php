<?php 

namespace ZubZet\Framework\Routing;

class PendingRoute extends PendingRoutingState {


    public function __construct(
        private string $method,
        private string $endpoint,
        public PendingAction $action,
    ) {}

    public function __destruct() {
        Route::performRoute(
            $this->method,
            $this->endpoint,
            $this->action,
            $this->middleware,
            $this->afterMiddleware
        );
    }
}

?>