<?php

    namespace ZubZet\Framework\Support\Checkpoint;

    /**
     * Snapshot / Restore selected instance properties via a Checkpoint handle.
     */
    trait CanCheckpoint {

        /**
         * Snapshot selected properties into a Checkpoint for later restore()
         * */
        public function checkpointCurrentState(?array $properties = null, ?string $attributeClass = null): Checkpoint {
            if(is_null($properties)) {
                $properties = $this->checkpointablePropertyNames($attributeClass);
            }
            return new Checkpoint($this, $properties);
        }

        /** @return string[] Filtered by $attributeClass when given, else all non-static instance properties. */
        protected function checkpointablePropertyNames(?string $attributeClass = IncludeInCheckpoint::class): array {
            $names = [];
            foreach((new \ReflectionClass($this))->getProperties() as $property) {
                if($property->isStatic()) continue;
                if(!is_null($attributeClass) && empty($property->getAttributes($attributeClass))) continue;
                $names[] = $property->getName();
            }
            return $names;
        }
    }

?>
