<?php

    namespace Tests\Support;

    use JsonException;
    use Tests\TestCase;
    use ZubZet\Framework\Support\Rest;

    class RestTest extends TestCase {

        // The reply

        public function testPrintsThePayloadAfterAMetaBlock(): void {
            $before = time();
            $reply = $this->reply(["result" => "success", "id" => 5], ["users", "show", "5"]);
            $after = time();

            $this->assertSame(["meta", "result", "id"], array_keys($reply));
            $this->assertSame("success", $reply["result"]);
            $this->assertSame(5, $reply["id"]);

            $this->assertSame(["endpoint", "request", "timestamp"], array_keys($reply["meta"]));
            $this->assertSame("REST API", $reply["meta"]["endpoint"]);
            $this->assertSame("users/show/5", $reply["meta"]["request"]);
            $this->assertGreaterThanOrEqual($before, $reply["meta"]["timestamp"]);
            $this->assertLessThanOrEqual($after, $reply["meta"]["timestamp"]);
        }

        public function testAnEmptyPayloadLeavesOnlyTheMetaBlock(): void {
            $reply = $this->reply([], []);

            $this->assertSame(["meta"], array_keys($reply));
            $this->assertSame("", $reply["meta"]["request"]);
        }

        // Documented: a "meta" key of the payload takes the place of the block
        public function testAMetaKeyOfThePayloadReplacesTheMetaBlock(): void {
            $reply = $this->reply(["meta" => "mine", "other" => 1], ["a"]);

            $this->assertSame(["meta" => "mine", "other" => 1], $reply);
        }

        public function testPrintsPrettyJson(): void {
            $printed = $this->printed(["a" => ["b" => 1]], []);

            $this->assertStringStartsWith("{\n    \"meta\": {\n", $printed);
            $this->assertStringEndsWith("\n}", $printed);
        }

        /** @dataProvider payloads */
        public function testEveryValueArrivesAsItWasGiven(mixed $value): void {
            $this->assertSame($value, $this->reply(["value" => $value], [])["value"]);
        }

        public static function payloads(): array {
            return [
                "utf-8 text" => ["äöü € 日本"],
                "markup" => ['<a href="/x?a=1&b=2">it\'s</a>'],
                "a slash and a backslash" => ["a/b\\c"],
                "a line break and a tab" => ["line one\nline two\tend"],
                "an empty string" => [""],
                "null" => [null],
                "a boolean" => [false],
                "an integer" => [-5],
                "a float" => [1.5],
                "a list" => [[1, "two", null]],
                "an empty list" => [[]],
                "a nested object" => [["a" => ["b" => ["c" => "deep"]]]],
                "numeric keys that are no list" => [[5 => "five", 7 => "seven"]],
            ];
        }

        // One broken byte used to empty the whole reply: json_encode() gives up on text that is no
        // UTF-8. The reply is what tells the client what happened, so it goes out with the broken
        // bytes replaced, like a browser shows them.
        /** @dataProvider textsThatAreNoUtf8 */
        public function testTextThatIsNoUtf8ArrivesWithTheBrokenBytesReplaced(string $text, string $expected): void {
            $reply = $this->reply(["message" => $text, "kept" => "äöü"], []);

            $this->assertSame($expected, $reply["message"]);
            $this->assertSame("äöü", $reply["kept"]);
        }

        public static function textsThatAreNoUtf8(): array {
            return [
                "Latin-1 text, from a file that was not converted" => ["Fr\xfchdienst", "Fr\u{FFFD}hdienst"],
                "a character cut in half by substr()" => [substr("Größe", 0, 3), "Gr\u{FFFD}"],
                "raw bytes" => ["\xff\xfe", "\u{FFFD}\u{FFFD}"],
            ];
        }

        public function testAKeyThatIsNoUtf8IsReplacedToo(): void {
            $reply = $this->reply(["Fr\xfch" => 1], []);

            $this->assertSame(["meta", "Fr\u{FFFD}h"], array_keys($reply));
        }

        // A number that is none is a mistake of the code, not of the data, and stays loud. It used to
        // be answered with an empty body and no error
        /** @dataProvider payloadsThatCannotBeEncoded */
        public function testAPayloadThatCannotBeEncodedThrowsInsteadOfPrintingNothing(mixed $value): void {
            $rest = new Rest(["value" => $value], []);

            ob_start();
            try {
                $rest->execute(false);
                $this->fail("Expected a JsonException, but nothing was thrown.");
            } catch(JsonException $thrown) {
                $this->assertNotSame("", $thrown->getMessage());
            } finally {
                $printed = ob_get_clean();
            }

            $this->assertSame("", $printed);
        }

        public static function payloadsThatCannotBeEncoded(): array {
            return [
                "not a number" => [NAN],
                "infinity" => [INF],
            ];
        }

        /**
         * @param array<string|int, mixed> $payload
         * @param string[] $urlParts
         * @return array<string|int, mixed> The decoded reply
         */
        private function reply(array $payload, array $urlParts): array {
            return json_decode($this->printed($payload, $urlParts), true, 512, JSON_THROW_ON_ERROR);
        }

        /**
         * @param array<string|int, mixed> $payload
         * @param string[] $urlParts
         */
        private function printed(array $payload, array $urlParts): string {
            ob_start();
            try {
                (new Rest($payload, $urlParts))->execute(false);
            } finally {
                $printed = ob_get_clean();
            }

            return $printed;
        }
    }

?>
