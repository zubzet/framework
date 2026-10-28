<?php

namespace ZubZet\Framework\Routing;

class PendingRoutingState {

    public array $middleware = [];
    public array $afterMiddleware = [];

    public function middleware(array $middleware, array $arguments = []): self {
        $this->middleware[] = new PendingAction($middleware, $arguments);
        return $this;
    }

    public function afterMiddleware(array $afterMiddleware, array $arguments = []): self {
        $this->afterMiddleware[] = new PendingAction($afterMiddleware, $arguments);
        return $this;
    }
}
?>