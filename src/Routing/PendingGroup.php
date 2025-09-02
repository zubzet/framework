<?php 

namespace ZubZet\Framework\Routing;

class PendingGroup extends PendingRoutingState {

    private string $prefix;
    private $callback;

    public function __construct(string $prefix, callable $callback) {
        $this->prefix = $prefix;
        $this->callback = $callback;
    }

    public function __destruct() {
        Route::performGroup(
            $this->prefix, 
            $this->callback, 
            ...$this->middleware
        );
    }
}

?>