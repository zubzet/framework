<?php

    namespace ZubZet\Framework\Support\Checkpoint;

    /**
     * Handle returned by CanCheckpoint::checkpointCurrentState().
     */
    class Checkpoint {

        /** @var array<string, mixed> Captured values for properties that were initialized at snapshot time. */
        private array $state = [];

        /** @var string[] Names of properties that were uninitialized at snapshot time. */
        private array $uninitialized = [];

        public function __construct(private Checkpointable $target, array $properties) {
            foreach($properties as $name) {
                if(!property_exists($target, $name)) continue;

                $reflection = new \ReflectionProperty($target, $name);
                if($reflection->isInitialized($target)) {
                    $this->state[$name] = $target->$name;
                    continue;
                }
                $this->uninitialized[] = $name;
            }
        }

        public function restore(): void {
            foreach($this->state as $name => $value) {
                $this->target->$name = $value;
            }
            foreach($this->uninitialized as $name) {
                unset($this->target->$name);
            }
        }
    }

?>
