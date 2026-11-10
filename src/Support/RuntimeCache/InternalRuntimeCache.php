<?php

    namespace ZubZet\Framework\Support\RuntimeCache;

    /**
     * The framework's own store to ensure no collisions with the runtime cache of
     * user space or extensions can happen.
     * @internal
     */
    final class InternalRuntimeCache extends RuntimeCache {}

?>
