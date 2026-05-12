<?php

    /**
     * Probes for global references and pure helper functions used by
     * tests/cypress/e2e/support/.
     */
    class HelperController extends z_controller {

        public function action_zubzet(Request $req, Response $res) {
            print_r(zubzet()->custom_value);
        }

        public function action_model(Request $req, Response $res) {
            echo model("Helper")->testCall();
        }

        public function action_request(Request $req, Response $res) {
            print_r(request()->getParameters(0, 1));
        }

        public function action_response(Request $req, Response $res) {
            response()->generateRest([
                "response" => "success",
            ]);
        }

        public function action_config(Request $req, Response $res) {
            print_r(config("custom_value"));
        }

        public function action_user(Request $req, Response $res) {
            print_r(user()->isLoggedIn ? "logged in" : "not logged in");
        }

        public function action_db(Request $req, Response $res) {
            is_null(db()->queryBuilderConnection) ? print_r("no database") : print_r("database connected");
        }

        public function action_view(Request $req, Response $res) {
            view("core/render", [
                "data" => "HelperFunction",
            ]);
        }

        /*
         * Pure global helpers from src/Support/Helpers.php.
         * Each action runs the helper against its probe cases and returns a
         * {allPassed, results} JSON via runTests / runTest. Cypress only
         * checks that allPassed is true.
         */

        public function action_function_makeSlug(Request $req, Response $res) {
            return $this->runTests([
                "Hello World!" => "hello-world",
                "  Already-Slug  " => "already-slug",
                "Snake_case_input" => "snake-case-input",
                "Multi   spaces---and!" => "multi-spaces-and",
                "@@@" => "",
            ], fn($input, $expected) => makeSlug($input) === $expected);
        }

        public function action_function_uecho(Request $req, Response $res) {
            return $this->runTests([
                "<b>x</b>" => "x",
                "a & b" => "a &amp; b",
                "<script>x</script>" => "x",
                "no tags" => "no tags",
            ], function($input, $expected) {
                ob_start();
                uecho($input);
                return ob_get_clean() === $expected;
            });
        }

        public function action_function_shortenStr(Request $req, Response $res) {
            return $this->runTest("shortenStr (3 signatures)",
                shortenStr("hi", 10) === "hi "
                && shortenStr("hello world", 10) === "hello w..."
                && shortenStr("abcdefghij", 5, "!") === "abcd!"
            );
        }

        public function action_function_de_strtolower(Request $req, Response $res) {
            // ß is intentionally transliterated to "ss" (see src/Support/Helpers.php).
            return $this->runTests([
                "HELLO" => "hello",
                "STRASSE" => "strasse",
                "GROßE STÄDTE: ÜBER ÖL" => "grosse städte: über öl",
            ], fn($input, $expected) => de_strtolower($input) === $expected);
        }

        public function action_function_var_swap(Request $req, Response $res) {
            $a = "first";
            $b = "second";
            var_swap($a, $b);

            return $this->runTest(
                "var_swap swaps two refs",
                $a === "second" && $b === "first",
            );
        }

        public function action_function_emptyToNull(Request $req, Response $res) {
            return $this->runTests([
                "" => null,
                "null" => null,
                "0" => null,
                "real" => "real",
            ], function($input, $expected) {
                emptyToNull($input);
                return $input === $expected;
            });
        }

        public function action_function_getCaller(Request $req, Response $res) {
            // Chain: action_function_getCaller -> caller_helper -> getCaller(1)
            // getCaller(1) returns 2 frames up, i.e. the action itself.
            return $this->runTest(
                "getCaller(1) reports the calling action",
                $this->caller_helper() === "action_function_getCaller",
            );
        }

        private function caller_helper() {
            return getCaller();
        }

        private function runTests($cases, $testFn) {
            $allPassed = true;
            $results = [];
            foreach($cases as $input => $expected) {
                $isPassing = $testFn($input, $expected);
                $results[] = "$input => $expected: " . ($isPassing ? "PASS" : "FAIL");
                if(!$isPassing) $allPassed = false;
            }
            return response()->json([
                "allPassed" => $allPassed,
                "results" => $results,
            ]);
        }

        private function runTest($description, $isPassing) {
            return response()->json([
                "allPassed" => $isPassing,
                "results" => ["$description: " . ($isPassing ? "PASS" : "FAIL")],
            ]);
        }

    }

?>
