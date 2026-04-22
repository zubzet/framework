<?php

    namespace ZubZet\Framework\Support\Checkpoint;

    /**
     * Contract for classes that want checkpoint/restore of their state.
     * The method is satisfied by `use CanCheckpoint;`, so any implementer
     * that doesn't use the trait must provide its own equivalent.
     */
    interface Checkpointable {
        public function checkpointCurrentState(?array $properties = null, ?string $attributeClass = null): Checkpoint;
    }

?>
