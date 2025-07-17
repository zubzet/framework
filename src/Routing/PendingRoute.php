<?php 

namespace ZubZet\Framework\Routing;

class PendingRoute extends PendingRoutingState {

    public function __construct(
        private string $method,
        private string $endpoint,
        private array $action,
    ) {}

    public function __destruct() {
        if(str_ends_with($this->endpoint, '/*')) {
            $this->endpoint = substr_replace($this->endpoint, '{param:.*}', -1);
        }

        Route::performRoute(
            $this->method,
            $this->endpoint,
            $this->action,
            ...$this->middleware
        );
    }
}

?>