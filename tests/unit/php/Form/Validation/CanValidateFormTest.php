<?php

    namespace Tests\Form\Validation;

    use Tests\TestCase;
    use ZubZet\Framework\Form\Validation\Field;
    use ZubZet\Framework\Form\Validation\Result;

    class CanValidateFormTest extends TestCase {

        private const ERROR_INTEGER = ["name" => "ints", "type" => "integer"];

        // integer

        /** @dataProvider validIntegers */
        public function testIntegerAcceptsValidIntegers(string $ints, int $cast): void {
            $result = $this->validateInteger($ints);

            $this->assertFalse($result->hasErrors);
            $this->assertSame([], $result->errors);
            $this->assertSame($cast, $result->getValue("ints"));
        }

        public static function validIntegers(): array {
            return [
                "42" => ["42", 42],
                "-5" => ["-5", -5],
                "999999" => ["999999", 999999],
                "largest integer" => [(string) PHP_INT_MAX, PHP_INT_MAX],
                "explicit plus sign" => ["+5", 5],
                "negative zero" => ["-0", 0],
            ];
        }

        /** @dataProvider invalidIntegers */
        public function testIntegerRejectsNonIntegerInput(string $ints): void {
            $result = $this->validateInteger($ints);

            $this->assertTrue($result->hasErrors);
            $this->assertSame([self::ERROR_INTEGER], $result->errors);
        }

        public static function invalidIntegers(): array {
            return [
                "abc" => ["abc"],
                "1.5" => ["1.5"],
                "not-a-number" => ["not-a-number"],
                "one above the largest integer" => ["9223372036854775808"],
                "leading zeros" => ["007"],
            ];
        }

        public function testIntegerAcceptsZero(): void {
            $result = $this->validateInteger("0");

            $this->assertFalse($result->hasErrors);
            $this->assertSame([], $result->errors);
            $this->assertSame(0, $result->getValue("ints"));
        }

        // Empty input is intentionally not rejected: the rules other than
        // required() are skipped when the value is missing or empty.
        public function testIntegerSkipsAMissingOrEmptyValue(): void {
            $empty = $this->validateInteger("");
            $missing = $this->request($this->input())->validateForm([(new Field("ints"))->integer()]);

            foreach([$empty, $missing] as $result) {
                $this->assertFalse($result->hasErrors);
                $this->assertSame([], $result->errors);
                $this->assertNull($result->getValue("ints"));
            }
        }

        private function validateInteger(string $ints): Result {
            return $this->request($this->input()->withPost(["ints" => $ints]))
                ->validateForm([(new Field("ints"))->integer()]);
        }
    }

?>
