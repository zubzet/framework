<?php

    namespace Tests\Support\Checkpoint;

    use PHPUnit\Framework\TestCase;
    use ZubZet\Framework\Support\Checkpoint\CanCheckpoint;
    use ZubZet\Framework\Support\Checkpoint\Checkpointable;
    use ZubZet\Framework\Support\Checkpoint\IncludeInCheckpoint;

    class CheckpointTest extends TestCase {

        public function testRestoresTheMarkedPropertiesByDefault(): void {
            $target = $this->getTarget();

            $checkpoint = $target->checkpointCurrentState();
            $target->marked = "changed";
            $target->unmarked = "changed";
            $checkpoint->restore();

            $this->assertSame("initial", $target->marked);
            $this->assertSame("changed", $target->unmarked);
        }

        public function testThePrivatePropertyDoesNotGetInTheWayByDefault(): void {
            $target = $this->getTarget();

            $target->checkpointCurrentState()->restore();

            $this->assertSame("initial", $target->marked);
        }

        public function testNamedPropertiesAreRestoredWhateverTheyAreMarkedAs(): void {
            $target = $this->getTarget();

            $checkpoint = $target->checkpointCurrentState(["unmarked"]);
            $target->marked = "changed";
            $target->unmarked = "changed";
            $checkpoint->restore();

            $this->assertSame("changed", $target->marked);
            $this->assertSame("initial", $target->unmarked);
        }

        public function testWithoutAnAttributeEveryPropertyIsRestored(): void {
            $target = new class implements Checkpointable {
                use CanCheckpoint;

                #[IncludeInCheckpoint]
                public string $marked = "initial";
                public string $unmarked = "initial";
            };

            $checkpoint = $target->checkpointCurrentState(attributeClass: null);
            $target->marked = "changed";
            $target->unmarked = "changed";
            $checkpoint->restore();

            $this->assertSame("initial", $target->marked);
            $this->assertSame("initial", $target->unmarked);
        }

        public function testAnUninitializedPropertyIsUninitializedAgain(): void {
            $target = new class implements Checkpointable {
                use CanCheckpoint;

                #[IncludeInCheckpoint]
                public string $late;
            };

            $checkpoint = $target->checkpointCurrentState();
            $target->late = "set";
            $checkpoint->restore();

            $this->assertFalse(isset($target->late));
        }

        private function getTarget(): Checkpointable {
            return new class implements Checkpointable {
                use CanCheckpoint;

                #[IncludeInCheckpoint]
                public string $marked = "initial";
                public string $unmarked = "initial";
                private string $hidden = "initial";
            };
        }
    }

?>
