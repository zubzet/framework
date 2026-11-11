<?php

    namespace Tests\Support;

    use InvalidArgumentException;
    use Tests\TestCase;
    use ZubZet\Framework\Support\HasDynamicAttributes;

    class HasDynamicAttributesTest extends TestCase {

        private const ERROR_STORE = "The attribute store cannot be accessed directly.";

        // Guards

        public function testRejectsDirectGetOfTheInternalDynamicAttributesStore(): void {
            $target = $this->target();

            $this->assertThrows(InvalidArgumentException::class, self::ERROR_STORE, fn() => $target->dynamicAttributesStore);
        }

        public function testRejectsDirectIssetOfTheInternalDynamicAttributesStore(): void {
            $target = $this->target();

            $this->assertThrows(InvalidArgumentException::class, self::ERROR_STORE, fn() => isset($target->dynamicAttributesStore));
        }

        public function testThrowsWhenReadingAnAttributeThatWasNeverSet(): void {
            $target = $this->target();

            $this->assertThrows(
                InvalidArgumentException::class,
                "The attribute 'never_set' does not exist in the attribute store (yet).",
                fn() => $target->never_set,
            );
        }

        // Attributes

        public function testAnAttributeIsReadBackAsItWasSet(): void {
            $target = $this->target();

            $target->name = "value";

            $this->assertSame("value", $target->name);
        }

        public function testAStoredNullIsReadBackAndCountsAsSet(): void {
            $target = $this->target();

            $target->name = null;

            $this->assertNull($target->name);
            // Unlike isset() on a plain property
            $this->assertTrue(isset($target->name));
        }

        public function testIssetIsFalseForAnAttributeThatWasNeverSet(): void {
            $this->assertFalse(isset($this->target()->never_set));
        }

        public function testReadingSettingsReturnsTheWholeStore(): void {
            $target = $this->target();
            $target->first = 1;
            $target->second = 2;

            $this->assertSame(["first" => 1, "second" => 2], $target->settings);
        }

        // Bulk access

        public function testGetAllAttributesReturnsTheStore(): void {
            $target = $this->target();
            $target->first = 1;

            $this->assertSame(["first" => 1], $target->getAllAttributes());
        }

        public function testSetAttributesReplacesTheStore(): void {
            $target = $this->target();
            $target->old = "old";

            $target->setAttributes(["new" => "new"]);

            $this->assertSame(["new" => "new"], $target->getAllAttributes());
            $this->assertFalse(isset($target->old));
        }

        public function testOverwriteAttributesMergesIntoTheStore(): void {
            $target = $this->target();
            $target->kept = "kept";
            $target->replaced = "old";

            $target->overwriteAttributes(["replaced" => "new", "added" => "added"]);

            $this->assertSame(["kept" => "kept", "replaced" => "new", "added" => "added"], $target->getAllAttributes());
        }

        private function target(): object {
            return new class {
                use HasDynamicAttributes;
            };
        }
    }

?>
