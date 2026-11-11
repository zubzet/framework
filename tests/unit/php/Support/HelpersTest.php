<?php

    namespace Tests\Support;

    use Tests\TestCase;

    class HelpersTest extends TestCase {

        protected function setUp(): void {
            $this->bootFramework();
        }

        // e

        /** @dataProvider eCases */
        public function testEProducesTheExpectedOutput(?string $input, ?string $expected): void {
            $this->assertSame($expected, e($input));
        }

        public static function eCases(): array {
            return [
                "null in, null out" => [null, null],
                "no tags, no specials" => ["plain text 123", "plain text 123"],
                "an empty string" => ["", ""],
                "strip_tags removes markup" => ["<b>bold</b>", "bold"],
                "scripts are stripped, content kept" => ["<script>alert(1)</script>", "alert(1)"],
                "a tag with an attribute" => ['<a href="x">link</a>', "link"],
                "an unclosed tag" => ["<b>x", "x"],
                "ampersand escaped" => ["a & b", "a &amp; b"],
                "double quote escaped" => ['quote " mark', "quote &quot; mark"],
                "strip first, then escape" => ["<em>x & y</em>", "x &amp; y"],
                "less-than before a space is no tag" => ["a < b", "a &lt; b"],
                "greater-than is escaped" => ["5 > 3", "5 &gt; 3"],
                "single quotes are escaped" => ["it's", "it&#039;s"],
                "existing entities are not double-encoded" => ["a &amp; b", "a &amp; b"],
            ];
        }

        // makeSlug

        /** @dataProvider slugCases */
        public function testMakeSlugProducesTheExpectedOutput(string $input, string $expected): void {
            $this->assertSame($expected, makeSlug($input));
        }

        public static function slugCases(): array {
            return [
                "words with punctuation" => ["Hello World!", "hello-world"],
                "surrounding whitespace" => ["  Already-Slug  ", "already-slug"],
                "underscores become dashes" => ["Snake_case_input", "snake-case-input"],
                "repeated separators collapse" => ["Multi   spaces---and!", "multi-spaces-and"],
                "only removed characters" => ["@@@", ""],
                "an empty string" => ["", ""],
                "mixed separators collapse" => ["a - b", "a-b"],
                "an underscore run collapses" => ["a__b", "a-b"],
                "leading and trailing dashes and underscores are trimmed" => ["-_a_-", "a"],
                "digits are kept" => ["x1y2", "x1y2"],
                // Documented: nothing outside A-Za-z0-9-_ and space survives
                "letters outside ASCII are dropped" => ["Größe", "gre"],
            ];
        }

        // uecho

        /** @dataProvider uechoCases */
        public function testUechoProducesTheExpectedOutput(string $input, string $expected): void {
            ob_start();
            uecho($input);

            $this->assertSame($expected, ob_get_clean());
        }

        public static function uechoCases(): array {
            return [
                "markup is stripped" => ["<b>x</b>", "x"],
                "ampersand escaped" => ["a & b", "a &amp; b"],
                "scripts are stripped" => ["<script>x</script>", "x"],
                "no tags" => ["no tags", "no tags"],
                "double quote escaped" => ['quote " mark', "quote &quot; mark"],
            ];
        }

        // shortenStr

        public function testShortenStrProducesTheExpectedOutput(): void {
            $this->assertSame("hi ", shortenStr("hi", 10));
            $this->assertSame("hello w...", shortenStr("hello world", 10));
            $this->assertSame("abcd!", shortenStr("abcdefghij", 5, "!"));
        }

        public function testShortenStrKeepsEveryWordThatFitsWithATrailingSpace(): void {
            $this->assertSame("a b c ", shortenStr("a b c", 10));
        }

        public function testShortenStrCutsInsideTheWordThatDoesNotFitAnymore(): void {
            $this->assertSame("one two t...", shortenStr("one two three four", 12));
        }

        public function testShortenStrAcceptsAnEmptyCutDescriptor(): void {
            $this->assertSame("hello wo", shortenStr("hello world", 8, ""));
        }

        public function testShortenStrCountsCharactersAndNotBytes(): void {
            $this->assertSame("äö...", shortenStr("äöüäöü", 5));
        }

        // de_strtolower

        /** @dataProvider lowercaseCases */
        public function testDeStrtolowerProducesTheExpectedOutput(string $input, string $expected): void {
            $this->assertSame($expected, de_strtolower($input));
        }

        public static function lowercaseCases(): array {
            return [
                "ascii" => ["HELLO", "hello"],
                "a double s stays" => ["STRASSE", "strasse"],
                // The sharp s is transliterated on purpose
                "umlauts and the sharp s" => ["GROßE STÄDTE: ÜBER ÖL", "grosse städte: über öl"],
                "an empty string" => ["", ""],
                "the sharp s alone" => ["ß", "ss"],
                "mixed case" => ["Mixed Case", "mixed case"],
            ];
        }

        // var_swap

        public function testVarSwapSwapsTwoReferences(): void {
            $a = "first";
            $b = "second";

            var_swap($a, $b);

            $this->assertSame("second", $a);
            $this->assertSame("first", $b);
        }

        public function testVarSwapSwapsValuesOfDifferentTypes(): void {
            $a = [1, 2];
            $b = null;

            var_swap($a, $b);

            $this->assertNull($a);
            $this->assertSame([1, 2], $b);
        }

        // emptyToNull

        /** @dataProvider emptyToNullCases */
        public function testEmptyToNullProducesTheExpectedOutput(mixed $input, mixed $expected): void {
            emptyToNull($input);

            $this->assertSame($expected, $input);
        }

        public static function emptyToNullCases(): array {
            return [
                "an empty string" => ["", null],
                "the string null" => ["null", null],
                "the string 0" => ["0", null],
                "a real value" => ["real", "real"],
                "the integer 0" => [0, null],
                "false" => [false, null],
                "an empty array" => [[], null],
                "null" => [null, null],
                "a space" => [" ", " "],
                "the string false" => ["false", "false"],
                "the string 0.0" => ["0.0", "0.0"],
                "a non-zero integer" => [1, 1],
            ];
        }

        // getCaller

        public function testGetCallerReportsTheCallingFunction(): void {
            // getCaller(1) names the function that called the function calling getCaller()
            $this->assertSame(__FUNCTION__, $this->callerOfTheHelper());
        }

        public function testGetCallerWithDepthZeroReportsTheFunctionCallingIt(): void {
            $this->assertSame("directCaller", $this->directCaller());
        }

        private function callerOfTheHelper(): string {
            return getCaller();
        }

        private function directCaller(): string {
            return getCaller(0);
        }
    }

?>
