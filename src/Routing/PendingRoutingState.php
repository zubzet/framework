<?php

namespace ZubZet\Framework\Routing;

class PendingRoutingState {

    public array $middleware = [];

    public function middleware(array $middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }
}
?>