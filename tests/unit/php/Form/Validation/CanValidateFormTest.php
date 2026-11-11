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

        // regex

        /** @dataProvider regexValues */
        public function testRegexPassesOnlyWhenNothingIsLeftOver(string $expression, array $exceptions, string $value, bool $valid): void {
            $result = $this->validate((new Field("f"))->regex($expression, $exceptions), $value);

            $this->assertSame($valid ? [] : [["name" => "f", "type" => "regex"]], $result->errors);
        }

        public static function regexValues(): array {
            return [
                "only allowed characters" => ["/[A-Za-z ]/", [], "ab", true],
                "a digit is left over" => ["/[A-Za-z ]/", [], "a1", false],
                // "0" is falsy, the old comparison took it for an empty rest
                "a zero is left over" => ["/[A-Za-z ]/", [], "a0", false],
                "only a zero" => ["/[A-Za-z ]/", [], "0", false],
                "allowed characters and exceptions" => ["/[A-Za-z ]/", ["-", "!"], "a-b!", true],
                "nothing but exceptions" => ["/[A-Za-z ]/", ["-", "!"], "-!", true],
                "a character that is no exception" => ["/[A-Za-z ]/", ["-", "!"], "a?", false],
                "an anchored expression that matches" => ["/^[a-z]+$/", [], "abc", true],
                "an anchored expression that does not match" => ["/^[a-z]+$/", [], "ab1", false],
            ];
        }

        // checked

        /** @dataProvider checkedValues */
        public function testCheckedAcceptsOnlyTheExactSpellings(string $value, bool $valid): void {
            $result = $this->validate((new Field("f"))->checked(), $value);

            $this->assertSame($valid ? [] : [["name" => "f", "type" => "in"]], $result->errors);
        }

        public static function checkedValues(): array {
            return [
                "1" => ["1", true],
                "true" => ["true", true],
                "on" => ["on", true],
                "0" => ["0", false],
                "off" => ["off", false],
                // Loosely equal to "1", but not one of the allowed spellings
                "a leading zero" => ["01", false],
                "a decimal" => ["1.0", false],
                "a plus sign" => ["+1", false],
                "a leading space" => [" 1", false],
            ];
        }

        // An allow-list of integers still accepts the strings a form sends
        public function testInComparesAsTextSoAnIntegerListAcceptsPostedStrings(): void {
            $field = fn() => (new Field("f"))->in([1, 2, 3]);

            $this->assertSame([], $this->validate($field(), "2")->errors);
            $this->assertSame([["name" => "f", "type" => "in"]], $this->validate($field(), "02")->errors);
            $this->assertSame([["name" => "f", "type" => "in"]], $this->validate($field(), "4")->errors);
        }

        // date

        /** @dataProvider dateValues */
        public function testDateAcceptsEveryDateStrtotimeCanRead(string $value, bool $valid): void {
            $result = $this->validate((new Field("f"))->date(), $value);

            $this->assertSame($valid ? [] : [["name" => "f", "type" => "date"]], $result->errors);
        }

        public static function dateValues(): array {
            return [
                "a date" => ["2000-01-01", true],
                // strtotime() returns 0 here, which is a timestamp and not a failure
                "the Unix epoch" => ["1970-01-01 00:00:00 UTC", true],
                "text that is no date" => ["garbage", false],
            ];
        }

        // An explicitly given data set is used as it is, also when it is empty
        public function testAnEmptyDataSetIsNotReplacedByThePost(): void {
            $request = $this->request($this->input()->withPost(["f" => "from the post"]));

            $result = $request->validateForm([(new Field("f"))->required()], []);

            $this->assertSame([["name" => "f", "type" => "required"]], $result->errors);
        }

        public function testWithoutADataSetThePostIsValidated(): void {
            $request = $this->request($this->input()->withPost(["f" => "from the post"]));

            $result = $request->validateForm([(new Field("f"))->required()]);

            $this->assertSame([], $result->errors);
            $this->assertSame("from the post", $result->getValue("f"));
        }

        private function validate(Field $field, string $value): Result {
            return $this->request($this->input()->withPost(["f" => $value]))->validateForm([$field]);
        }

        private function validateInteger(string $ints): Result {
            return $this->request($this->input()->withPost(["ints" => $ints]))
                ->validateForm([(new Field("ints"))->integer()]);
        }
    }

?>
