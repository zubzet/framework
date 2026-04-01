<?php 

namespace ZubZet\Framework\Routing;

class PendingRoute extends PendingRoutingState {

    /** @var callable|array */
    private $action;

    public function __construct(
        private string $method,
        private string $endpoint,
        callable|array $action,
    ) {
        $this->action = $action;
    }

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