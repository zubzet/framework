<?php

    namespace ZubZet\Framework\Support\Checkpoint;

    /** Marks a property for CanCheckpoint snapshots that filter by this attribute; checkpointCurrentState() only filters when IncludeInCheckpoint::class is passed as $attributeClass. */
    #[\Attribute(\Attribute::TARGET_PROPERTY)]
    class IncludeInCheckpoint {}

?>
