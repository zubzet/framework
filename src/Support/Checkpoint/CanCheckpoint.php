<?php

    namespace ZubZet\Framework\Support\Checkpoint;

    /**
     * Snapshot / Restore selected instance properties via a Checkpoint handle.
     */
    trait CanCheckpoint {

        /**
         * Snapshot selected properties into a Checkpoint for later restore()
         * Only public properties can be captured and restored.
         * @param string[]|null $properties Names to snapshot; null discovers all non-static properties.
         * @param string|null $attributeClass With null $properties, keeps only properties carrying this attribute; the default null applies no IncludeInCheckpoint filtering.
         * */
        public function checkpointCurrentState(?array $properties = null, ?string $attributeClass = null): Checkpoint {
            if(is_null($properties)) {
                $properties = $this->checkpointablePropertyNames($attributeClass);
            }
            return new Checkpoint($this, $properties);
        }

        /**
         * @param string|null $attributeClass Keeps only properties carrying this attribute; defaults to IncludeInCheckpoint, null applies no filtering.
         * @return string[] Names of non-static instance properties, including non-public ones.
         * */
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
