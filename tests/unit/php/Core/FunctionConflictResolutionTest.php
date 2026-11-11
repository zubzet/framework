<?php

    namespace Tests\Core;

    use RuntimeException;
    use Tests\TestCase;
    use ZubZet\Framework\Core\FunctionConflictResolution;

    class FunctionConflictResolutionTest extends TestCase {

        // A function PHP always has, and one that nothing declares
        private const DEFINED = "strlen";
        private const UNDEFINED = "zubzet_unit_test_undefined_function";

        private const ERROR_CONFLICT = "The function 'strlen' is already defined, but is required by ZubZet\n"
            . "There might be a second package, which includes a function with the same name.\n"
            . "Please remove the source of the conflict to proceed.";

        // Conflicts

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function testThrowsWhenAnAppRedefinesOneOfTheGlobalHelperFunctions(): void {
            // The app declares the function before the framework is loaded, which
            // has to happen in a process where the framework has not declared it yet.
            // eval() because a declaration in this file would land in the test namespace
            eval("function model() {}");

            $this->assertThrows(RuntimeException::class, str_replace("strlen", "model", self::ERROR_CONFLICT), fn() => new \ZubZet\Framework\Support\GlobalReferences);
        }

        public function testRequireAndThenThrowsForADefinedFunctionWithoutRunningTheDeclaration(): void {
            $ran = false;

            $this->assertThrows(RuntimeException::class, self::ERROR_CONFLICT, function() use (&$ran) {
                FunctionConflictResolution::requireAndThen(self::DEFINED, function() use (&$ran) {
                    $ran = true;
                });
            });

            $this->assertFalse($ran);
        }

        public function testRequireAndThenRunsTheDeclarationForAnUndefinedFunction(): void {
            $ran = false;

            FunctionConflictResolution::requireAndThen(self::UNDEFINED, function() use (&$ran) {
                $ran = true;
            });

            $this->assertTrue($ran);
        }

        public function testOptionalAndThenDoesNothingForADefinedFunction(): void {
            $ran = false;

            FunctionConflictResolution::optionalAndThen(self::DEFINED, function() use (&$ran) {
                $ran = true;
            });

            $this->assertFalse($ran);
        }

        public function testOptionalAndThenRunsTheDeclarationForAnUndefinedFunction(): void {
            $ran = false;

            FunctionConflictResolution::optionalAndThen(self::UNDEFINED, function() use (&$ran) {
                $ran = true;
            });

            $this->assertTrue($ran);
        }

        public function testResolveThrowsAStringAsItIs(): void {
            $this->assertThrows(RuntimeException::class, "a message", fn() => FunctionConflictResolution::resolve("a message"));
        }

        public function testResolveJoinsAnArrayOfLinesWithNewlines(): void {
            $this->assertThrows(RuntimeException::class, "one\ntwo", fn() => FunctionConflictResolution::resolve(["one", "two"]));
        }
    }

?>
