<?php 

namespace ZubZet\Framework\Routing;

class PendingRoute extends PendingRoutingState {

    public function __construct(
        private string $method,
        private string $endpoint,
        private array $action,
    ) {}

    public function __destruct() {
<<<<<<< HEAD
=======
        if(str_ends_with($this->endpoint, '/*')) {
            $this->endpoint = substr_replace($this->endpoint, '{param:.*}', -1);

            Route::performFallback(
                $this->endpoint, 
                $this->method, 
                $this->action, 
                $this->middleware,
                $this->afterMiddleware
            );
            return;
        }

>>>>>>> dc14d76 (✨ Afterware)
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