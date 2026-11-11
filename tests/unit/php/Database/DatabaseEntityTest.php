<?php

    namespace Tests\Database;

    use Tests\TestCase;
    use ZubZet\Framework\Database\DatabaseEntity;

    class DatabaseEntityTest extends TestCase {

        // Construction

        public function testHoldsTheDataItWasBuiltWith(): void {
            $this->assertSame(["id" => 1, "name" => "x"], $this->object(["name" => "x"])->getAll());
        }

        public function testKeepsTheIdItWasBuiltWith(): void {
            $this->assertSame(3, $this->object(["id" => 3])->id());
        }

        // Fields

        public function testReadsAFieldThatWasSet(): void {
            $object = $this->object();

            $object->put("name", "value");

            $this->assertSame("value", $object->getField("name"));
        }

        public function testReadsAFieldThatIsNull(): void {
            $object = $this->object();

            $object->put("name", null);

            $this->assertNull($object->getField("name"));
        }

        public function testThrowsOnAnUnknownField(): void {
            $this->assertThrows(\InvalidArgumentException::class, "Field 'missing' does not exist", fn() => $this->object(["name" => "x"])->getField("missing"));
        }

        public function testReadsTheUuidThroughItsField(): void {
            $this->assertSame("abc", $this->object(["uuid" => "abc"])->uuid());
        }

        public function testClearsEveryField(): void {
            $object = $this->object(["name" => "x"]);

            $object->clearFields();

            $this->assertSame([], $object->getAll());
        }

        // Detaching

        public function testDetachesFromItsIdAndData(): void {
            $object = $this->object(["name" => "x"]);

            $object->nullId();

            $this->assertThrows(\InvalidArgumentException::class, "Instance no longer exists", fn() => $object->id());
        }

        public function testRefusesEveryAccessOnceDetached(): void {
            $object = $this->object(["name" => "x"]);
            $object->nullId();

            foreach([
                fn() => $object->checkInstance(),
                fn() => $object->getField("name"),
                fn() => $object->getAll(),
                fn() => $object->clearFields(),
                fn() => $object->put("name", "y"),
                fn() => $object->uuid(),
            ] as $access) {
                $this->assertThrows(\InvalidArgumentException::class, "Instance no longer exists", $access);
            }
        }

        // Helpers

        /** @param array<string, mixed> $data */
        private function object(array $data = []): DatabaseEntity {
            // The protected setField() has no public way in
            return new class(array_merge(["id" => 1], $data)) extends DatabaseEntity {
                public function __construct(array $data) {
                    parent::__construct($data);
                    $this->data = $data;
                }

                public function put(string $field, mixed $value): void {
                    $this->setField($field, $value);
                }
            };
        }
    }

?>
