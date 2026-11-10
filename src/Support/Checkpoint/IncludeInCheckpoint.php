<?php

    namespace ZubZet\Framework\Support\Checkpoint;

    /** Marks a property for CanCheckpoint snapshots; checkpointCurrentState() takes the properties carrying it unless told otherwise. */
    #[\Attribute(\Attribute::TARGET_PROPERTY)]
    class IncludeInCheckpoint {}

?>
