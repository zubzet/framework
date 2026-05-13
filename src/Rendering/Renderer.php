<?php

    namespace ZubZet\Framework\Rendering;

    /**
     * Pluggable view-rendering engine. `CanRenderView` walks the registered
     * renderers and uses the first one that `supports()` the view.
     */
    interface Renderer {

        /** Whether this renderer can render the view at $viewPath. */
        public function supports(string $viewPath): bool;

        /** Render the view body and return it as a string. */
        public function render(string $viewPath, array $opt): string;

    }

?>
