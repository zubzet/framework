<?php

    namespace Tests\Message;

    use Tests\TestCase;
    use ZubZet\Framework\Message\EndOfRequest;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\Message\Response;

    /**
     * A call that ends the request throws EndOfRequest instead of calling exit.
     * That is what lets these tests call an action and still be there afterwards.
     * The REST helpers send a Content-Type header, which PHP refuses once the
     * test runner has printed anything, so every test runs in a process of its own.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    class EndOfRequestTest extends TestCase {

        protected function setUp(): void {
            $this->bootFramework(["host" => "http://localhost:8080", "rootDirectory" => ""]);
        }

        // An action as an application writes it, answered differently depending on the input
        private function action(Request $req, Response $res): void {
            if(empty($req->getPost("name"))) $res->error("The name is missing");

            $res->success(["name" => $req->getPost("name")]);
        }

        public function testAnActionCanBeCalledAndItsAnswerRead(): void {
            $req = $this->request($this->input()->withMethod("POST")->withPost(["name" => "Ada"]));

            $answer = $this->answerOf(fn() => $this->action($req, $this->response()));

            $this->assertSame("success", $answer["result"]);
            $this->assertSame("Ada", $answer["name"]);
        }

        public function testAnErrorStopsTheActionBeforeItsSuccess(): void {
            $req = $this->request($this->input()->withMethod("POST"));

            $answer = $this->answerOf(fn() => $this->action($req, $this->response()));

            $this->assertSame("error", $answer["result"]);
            $this->assertSame("The name is missing", $answer["message"]);
        }

        public function testNothingRunsAfterTheEnd(): void {
            $this->request();
            $reached = false;

            $this->answerOf(function() use (&$reached) {
                $this->response()->success();
                $reached = true;
            });

            $this->assertFalse($reached);
        }

        public function testACatchOfExceptionDoesNotSeeTheEnd(): void {
            $this->request();
            $caught = false;

            $this->answerOf(function() use (&$caught) {
                try {
                    $this->response()->success();
                } catch(\Exception) {
                    $caught = true;
                }
            });

            $this->assertFalse($caught);
        }

        public function testFinallyBlocksRunOnTheWayOut(): void {
            $this->request();
            $cleanedUp = false;

            $this->answerOf(function() use (&$cleanedUp) {
                try {
                    $this->response()->success();
                } finally {
                    $cleanedUp = true;
                }
            });

            $this->assertTrue($cleanedUp);
        }

        public function testAReplyThatIsToldNotToEndDoesNotThrow(): void {
            $this->request();
            $reached = false;

            ob_start();
            try {
                $this->response()->generateRest(["a" => 1], false);
                $reached = true;
            } finally {
                ob_end_clean();
            }

            $this->assertTrue($reached);
        }

        // Runs what ends the request and returns the decoded reply it printed
        private function answerOf(callable $action): array {
            ob_start();
            try {
                $action();
                $this->fail("Expected the request to end, but the action returned.");
            } catch(EndOfRequest) {
                return json_decode(ob_get_contents(), true);
            } finally {
                ob_end_clean();
            }
        }
    }

?>
