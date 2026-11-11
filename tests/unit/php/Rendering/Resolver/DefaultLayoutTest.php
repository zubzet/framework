<?php

    namespace Tests\Rendering\Resolver;

    use Tests\TestCase;
    use UnderflowException;
    use ZubZet\Framework\Message\Response;

    class DefaultLayoutTest extends TestCase {

        private const FRAMEWORK_DEFAULT = "layout/default_layout.php";
        private const NEW_LAYOUT = "layout/new_layout";
        private const ALT_LAYOUT = "layout/alt_layout";

        private const ERROR_INSTANCE_UNDERFLOW = "popDefaultLayout called on an empty stack - every push must be matched by a single pop.";
        private const ERROR_GLOBAL_UNDERFLOW = "popGlobalDefaultLayout called on an empty stack - every push must be matched by a single pop.";

        protected function setUp(): void {
            parent::setUp();
            // A response registers itself with the framework when it is created
            $this->bootFramework();
            $this->emptyGlobalStack();
        }

        protected function tearDown(): void {
            // The stack lives in the class, so it would reach the next test
            $this->emptyGlobalStack();
            parent::tearDown();
        }

        // Default-layout resolution

        public function testFallsBackToFrameworkDefaultWhenNoOverrideIsSet(): void {
            $this->assertSame(self::FRAMEWORK_DEFAULT, $this->resolve(new Response));
        }

        public function testGlobalDefaultIsUsedWhenOnlyGlobalIsSet(): void {
            Response::setGlobalDefaultLayout(self::ALT_LAYOUT);

            $this->assertSame(self::ALT_LAYOUT, $this->resolve(new Response));
        }

        public function testInstanceDefaultIsUsedWhenOnlyInstanceIsSet(): void {
            $response = new Response;
            $response->setDefaultLayout(self::NEW_LAYOUT);

            $this->assertSame(self::NEW_LAYOUT, $this->resolve($response));
        }

        public function testInstanceDefaultBeatsGlobalDefault(): void {
            Response::setGlobalDefaultLayout(self::ALT_LAYOUT);
            $response = new Response;
            $response->setDefaultLayout(self::NEW_LAYOUT);

            $this->assertSame(self::NEW_LAYOUT, $this->resolve($response));
        }

        public function testInstancePushPopRestoresThePreviousLayout(): void {
            $response = new Response;

            $response->pushDefaultLayout(self::NEW_LAYOUT);
            $this->assertSame(self::NEW_LAYOUT, $this->resolve($response));

            $this->assertSame(self::NEW_LAYOUT, $response->popDefaultLayout());
            $this->assertSame(self::FRAMEWORK_DEFAULT, $this->resolve($response));
        }

        public function testGlobalPushPopRestoresThePreviousLayout(): void {
            $response = new Response;

            Response::pushGlobalDefaultLayout(self::ALT_LAYOUT);
            $this->assertSame(self::ALT_LAYOUT, $this->resolve($response));

            $this->assertSame(self::ALT_LAYOUT, Response::popGlobalDefaultLayout());
            $this->assertSame(self::FRAMEWORK_DEFAULT, $this->resolve($response));
        }

        public function testPopDefaultLayoutThrowsOnAnEmptyInstanceStack(): void {
            $this->assertThrows(
                UnderflowException::class,
                self::ERROR_INSTANCE_UNDERFLOW,
                fn() => (new Response)->popDefaultLayout(),
            );
        }

        public function testPopGlobalDefaultLayoutThrowsOnAnEmptyGlobalStack(): void {
            $this->assertThrows(
                UnderflowException::class,
                self::ERROR_GLOBAL_UNDERFLOW,
                fn() => Response::popGlobalDefaultLayout(),
            );
        }

        public function testNestedInstancePushPopUnwindsInLifoOrder(): void {
            $response = new Response;

            $response->pushDefaultLayout(self::ALT_LAYOUT);
            $this->assertSame(self::ALT_LAYOUT, $this->resolve($response));

            $response->pushDefaultLayout(self::NEW_LAYOUT);
            $this->assertSame(self::NEW_LAYOUT, $this->resolve($response));

            $this->assertSame(self::NEW_LAYOUT, $response->popDefaultLayout());
            $this->assertSame(self::ALT_LAYOUT, $this->resolve($response));

            $this->assertSame(self::ALT_LAYOUT, $response->popDefaultLayout());
            $this->assertSame(self::FRAMEWORK_DEFAULT, $this->resolve($response));
        }

        public function testNestedGlobalPushPopUnwindsInLifoOrder(): void {
            $response = new Response;

            Response::pushGlobalDefaultLayout(self::ALT_LAYOUT);
            Response::pushGlobalDefaultLayout(self::NEW_LAYOUT);
            $this->assertSame(self::NEW_LAYOUT, $this->resolve($response));

            $this->assertSame(self::NEW_LAYOUT, Response::popGlobalDefaultLayout());
            $this->assertSame(self::ALT_LAYOUT, $this->resolve($response));
        }

        public function testPoppingTheInstanceStackLeavesTheGlobalStackAlone(): void {
            $response = new Response;
            Response::pushGlobalDefaultLayout(self::ALT_LAYOUT);
            $response->pushDefaultLayout(self::NEW_LAYOUT);

            $response->popDefaultLayout();

            $this->assertSame(self::ALT_LAYOUT, $this->resolve($response));
            $this->assertThrows(UnderflowException::class, self::ERROR_INSTANCE_UNDERFLOW, fn() => $response->popDefaultLayout());
            $this->assertSame(self::ALT_LAYOUT, Response::popGlobalDefaultLayout());
        }

        public function testSettingADefaultLayoutDiscardsTheLayoutsPushedBefore(): void {
            $response = new Response;
            $response->pushDefaultLayout(self::ALT_LAYOUT);
            $response->pushDefaultLayout(self::ALT_LAYOUT);

            $response->setDefaultLayout(self::NEW_LAYOUT);

            $this->assertSame(self::NEW_LAYOUT, $response->popDefaultLayout());
            $this->assertThrows(UnderflowException::class, self::ERROR_INSTANCE_UNDERFLOW, fn() => $response->popDefaultLayout());
        }

        public function testSettingAGlobalDefaultLayoutDiscardsTheLayoutsPushedBefore(): void {
            Response::pushGlobalDefaultLayout(self::ALT_LAYOUT);
            Response::pushGlobalDefaultLayout(self::ALT_LAYOUT);

            Response::setGlobalDefaultLayout(self::NEW_LAYOUT);

            $this->assertSame(self::NEW_LAYOUT, Response::popGlobalDefaultLayout());
            $this->assertThrows(UnderflowException::class, self::ERROR_GLOBAL_UNDERFLOW, fn() => Response::popGlobalDefaultLayout());
        }

        public function testResponsesKeepTheirOwnInstanceStackButShareTheGlobalOne(): void {
            $first = new Response;
            $second = new Response;

            $first->setDefaultLayout(self::NEW_LAYOUT);
            Response::setGlobalDefaultLayout(self::ALT_LAYOUT);

            $this->assertSame(self::NEW_LAYOUT, $this->resolve($first));
            $this->assertSame(self::ALT_LAYOUT, $this->resolve($second));
        }

        private function resolve(Response $response): string {
            return $this->callHidden($response, "resolveDefaultLayout");
        }

        private function emptyGlobalStack(): void {
            $this->setHiddenStatic(Response::class, "globalDefaultLayoutStack", []);
        }
    }

?>
