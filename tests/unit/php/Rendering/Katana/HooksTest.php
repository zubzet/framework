<?php

    namespace Tests\Rendering\Katana;

    use Blade\Config;
    use Tests\TestCase;
    use ZubZet\Framework\Rendering\Katana\Hooks;

    class HooksTest extends TestCase {

        private Config $config;

        protected function setUp(): void {
            parent::setUp();
            $this->bootFramework();

            $this->config = new Config($this->temporaryDirectory());
            Hooks::register($this->config);
        }

        // Auth directives (@auth / @guest)

        /** @dataProvider loggedOutUsers */
        public function testLoggedOutOnlyTheGuestBranchesRender(bool $hasUserObject): void {
            if($hasUserObject) $this->currentUser(loggedIn: false);

            // The arguments are the permissions of the view: none, one the
            // user would not have and one a logged in user could have
            foreach([[], ["dashboard"], ["orders.view"]] as $arguments) {
                $this->assertFalse($this->directive("auth")(...$arguments), "@auth(" . implode(",", $arguments) . ")");
                $this->assertTrue($this->directive("guest")(...$arguments), "@guest(" . implode(",", $arguments) . ")");
            }
        }

        public static function loggedOutUsers(): array {
            return [
                "an anonymous user" => [true],
                "no user object yet" => [false],
            ];
        }

        public function testLoggedInWithoutAnArgumentOnlyTheAuthBranchRenders(): void {
            $this->currentUser(loggedIn: true);

            $this->assertTrue($this->directive("auth")());
            $this->assertFalse($this->directive("guest")());
        }

        private function directive(string $name): callable {
            return $this->config->getDirective($name);
        }
    }

?>
