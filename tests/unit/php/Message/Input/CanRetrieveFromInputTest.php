<?php

    namespace Tests\Message\Input;

    use Tests\TestCase;

    class CanRetrieveFromInputTest extends TestCase {

        private const UPLOAD = [
            "name" => "TestFile_Small.txt",
            "type" => "text/plain",
            "tmp_name" => "/tmp/phpUnitUpload",
            "error" => 0,
            "size" => 1024,
        ];

        protected function setUp(): void {
            $this->bootFramework();
        }

        public function testGet(): void {
            $request = $this->request($this->input()->withGet(["TestGet" => "JustATest"]));

            $this->assertSame("JustATest", $request->getGet("TestGet"));
        }

        public function testGetReturnsTheDefaultForAMissingKey(): void {
            $request = $this->request($this->input()->withGet(["TestGet" => "JustATest"]));

            $this->assertNull($request->getGet("missing"));
            $this->assertSame("fallback", $request->getGet("missing", "fallback"));
        }

        public function testGetKeepsAnEmptyStringAndAZeroInsteadOfTheDefault(): void {
            $request = $this->request($this->input()->withGet(["empty" => "", "zero" => "0"]));

            $this->assertSame("", $request->getGet("empty", "fallback"));
            $this->assertSame("0", $request->getGet("zero", "fallback"));
        }

        public function testGetNoKeyReturnsTheFullQueryStringArray(): void {
            $request = $this->request($this->input()->withGet(["alpha" => "one", "beta" => "two", "gamma" => "3"]));

            $this->assertSame(["alpha" => "one", "beta" => "two", "gamma" => "3"], $request->getGet());
        }

        public function testGetNoKeyReturnsAnEmptyArrayWithoutAQuery(): void {
            $this->assertSame([], $this->request()->getGet());
        }

        public function testPost(): void {
            $request = $this->request($this->input()->withPost(["TestPost" => "JustATest"]));

            $this->assertSame("JustATest", $request->getPost("TestPost"));
        }

        public function testPostReturnsTheDefaultForAMissingKey(): void {
            $request = $this->request($this->input()->withPost(["TestPost" => "JustATest"]));

            $this->assertNull($request->getPost("missing"));
            $this->assertSame("fallback", $request->getPost("missing", "fallback"));
        }

        public function testPostNoKeyReturnsTheWholePostArray(): void {
            $post = ["TestPost" => "JustATest", "nested" => ["a" => "1"]];
            $request = $this->request($this->input()->withPost($post));

            $this->assertSame($post, $request->getPost());
        }

        public function testPostDoesNotReadTheQueryString(): void {
            $request = $this->request($this->input()->withGet(["TestPost" => "fromQuery"]));

            $this->assertNull($request->getPost("TestPost"));
        }

        public function testFile(): void {
            $request = $this->request($this->input()->withFiles(["file" => self::UPLOAD]));

            $this->assertSame("TestFile_Small.txt", $request->getFile("file")["name"]);
            $this->assertSame(self::UPLOAD, $request->getFile("file"));
        }

        public function testFileReturnsTheDefaultWhenNothingWasUploaded(): void {
            $this->assertSame("fallback", $this->request()->getFile("missing", "fallback"));
            $this->assertNull($this->request()->getFile("missing"));
        }

        public function testFileReturnsTheDefaultForAMissingFile(): void {
            $request = $this->request($this->input()->withFiles(["file" => self::UPLOAD]));

            $this->assertNull($request->getFile("missing"));
            $this->assertSame("fallback", $request->getFile("missing", "fallback"));
        }

        public function testFileNoKeyAndGetFilesReturnAllFiles(): void {
            $files = ["file" => self::UPLOAD, "other" => ["name" => "b.txt"] + self::UPLOAD];
            $request = $this->request($this->input()->withFiles($files));

            $this->assertSame($files, $request->getFile());
            $this->assertSame($files, $request->getFiles());
        }

        public function testCookie(): void {
            $request = $this->request($this->input()->withCookies(["theme" => "dark", "empty" => ""]));

            $this->assertSame("dark", $request->getCookie("theme"));
            $this->assertSame("", $request->getCookie("empty", "fallback"));
            $this->assertSame("fallback", $request->getCookie("missing", "fallback"));
            $this->assertNull($request->getCookie("missing"));
        }

        public function testCookieNoKeyAndGetCookiesReturnAllCookies(): void {
            $request = $this->request($this->input()->withCookies(["theme" => "dark", "lang" => "de"]));

            $this->assertSame(["theme" => "dark", "lang" => "de"], $request->getCookie());
            $this->assertSame(["theme" => "dark", "lang" => "de"], $request->getCookies());
        }
    }

?>
