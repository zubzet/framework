<?php

    namespace ZubZet\Framework\Support\Checkpoint;

    /** Marks a property for inclusion in CanCheckpoint's default snapshot. */
    #[\Attribute(\Attribute::TARGET_PROPERTY)]
    class IncludeInCheckpoint {}

?>
