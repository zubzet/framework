<?php 

namespace ZubZet\Framework\Routing;

class PendingRoute extends PendingRoutingState {

    private array $schema = [];

    public function __construct(
        private string $method,
        private string $endpoint,
        private array $action,
    ) {}

    public function schema(array $schema): self {
        $this->schema = $schema;
        return $this;
    }

    public function __destruct() {
        Route::performRoute(
            $this->method,
            $this->endpoint,
            $this->action,
            $this->middleware,
            $this->afterMiddleware,
            $this->schema
        );
    }
}

?>