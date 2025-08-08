<?php

namespace ZubZet\Framework\Routing;

class PendingRoutingState {

    public array $middleware = [];
    public array $afterMiddleware = [];

    public function middleware(array $middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function afterMiddleware(array $afterMiddleware): self {
        $this->afterMiddleware[] = $afterMiddleware;
        return $this;
    }
}
?>