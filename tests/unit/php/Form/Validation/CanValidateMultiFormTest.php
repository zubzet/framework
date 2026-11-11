<?php

    namespace Tests\Form\Validation;

    use Tests\TestCase;
    use ZubZet\Framework\Form\Validation\Field;

    class CanValidateMultiFormTest extends TestCase {

        // validation

        public function testRejectsEntriesThatFailFieldRulesNothingIsPersisted(): void {
            $rules = $this->rules();

            $result = $this->request($this->input()->withPost(["isFormData" => 1, "items" => [
                // length(1, 64) on name is violated by the empty string
                ["Z" => "create", "name" => "", "note" => "has-note"],
            ]]))->validateCED("items", $rules);

            $this->assertTrue($result->hasErrors);
            $this->assertFalse($result->doNothing);
            $this->assertSame([
                ["name" => "items", "type" => "required", "subname" => "name", "index" => 0],
            ], $result->errors);
        }

        public function testReportsErrorsPerFieldWithTheIndexOfTheOffendingRow(): void {
            $result = $this->request($this->input()->withPost(["isFormData" => 1, "items" => [
                ["Z" => "create", "name" => "ok", "note" => ""],
                ["Z" => "create", "name" => "", "note" => "ok"],
            ]]))->validateCED("items", $this->rules());

            $this->assertTrue($result->hasErrors);
            // Index 0: note missing. Index 1: name missing.
            $this->assertSame([
                ["name" => "items", "type" => "required", "subname" => "note", "index" => 0],
                ["name" => "items", "type" => "required", "subname" => "name", "index" => 1],
            ], $result->errors);
        }

        public function testReportsEveryFailedFieldOfOneRow(): void {
            $result = $this->request($this->input()->withPost(["items" => [
                ["Z" => "create", "name" => "", "note" => ""],
            ]]))->validateCED("items", $this->rules());

            $this->assertSame([
                ["name" => "items", "type" => "required", "subname" => "name", "index" => 0],
                ["name" => "items", "type" => "required", "subname" => "note", "index" => 0],
            ], $result->errors);
        }

        public function testReportsTheLimitsOfAFailedLengthRule(): void {
            $result = $this->request($this->input()->withPost(["items" => [
                ["Z" => "create", "name" => str_repeat("n", 64), "note" => "ok"],
                ["Z" => "create", "name" => str_repeat("n", 65), "note" => "ok"],
            ]]))->validateCED("items", $this->rules());

            $this->assertSame([
                ["name" => "items", "type" => "length", "info" => [1, 64], "subname" => "name", "index" => 1],
            ], $result->errors);
        }

        public function testKeepsTheKeyOfTheRowAsItsIndex(): void {
            $result = $this->request($this->input()->withPost(["items" => [
                5 => ["Z" => "create", "name" => "", "note" => "ok"],
            ]]))->validateCED("items", $this->rules());

            $this->assertSame([
                ["name" => "items", "type" => "required", "subname" => "name", "index" => 5],
            ], $result->errors);
        }

        public function testAcceptsRowsThatSatisfyTheRulesAndKeepsTheRulesForTheAction(): void {
            $rules = $this->rules();

            $result = $this->request($this->input()->withPost(["items" => [
                ["Z" => "create", "name" => "first", "note" => "one"],
                ["Z" => "edit", "dbId" => 1, "name" => "second", "note" => "two"],
            ]]))->validateCED("items", $rules);

            $this->assertFalse($result->hasErrors);
            $this->assertFalse($result->doNothing);
            $this->assertSame([], $result->errors);
            $this->assertSame("items", $result->name);
            $this->assertSame($rules, $result->fields);
        }

        // error fallthrough and no-op

        public function testOmittingTheCedKeyEntirelyIsANoOp(): void {
            $rules = $this->rules();

            $result = $this->request($this->input()->withPost([
                "isFormData" => 1,
                "noise" => "irrelevant",
            ]))->validateCED("items", $rules);

            $this->assertTrue($result->doNothing);
            $this->assertFalse($result->hasErrors);
            $this->assertSame([], $result->errors);
            $this->assertSame("items", $result->name);
            $this->assertSame($rules, $result->fields);
        }

        public function testAValueThatIsNotAListOfRowsIsANoOp(): void {
            $result = $this->request($this->input()->withPost(["items" => "not-rows"]))
                ->validateCED("items", $this->rules());

            $this->assertTrue($result->doNothing);
            $this->assertFalse($result->hasErrors);
            $this->assertSame([], $result->errors);
        }

        /** @return Field[] */
        private function rules(): array {
            return [
                (new Field("name"))->required()->length(1, 64),
                (new Field("note"))->required()->length(1, 255),
            ];
        }
    }

?>
