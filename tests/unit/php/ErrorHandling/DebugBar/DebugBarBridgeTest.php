<?php

    namespace Tests\ErrorHandling\DebugBar;

    use ReflectionClass;
    use Tests\TestCase;
    use ZubZet\Framework\ErrorHandling\DebugBar\DebugBarBridge;

    class DebugBarBridgeTest extends TestCase {

        protected function setUp(): void {
            $this->resetBridge();
        }

        protected function tearDown(): void {
            $this->resetBridge();
            parent::tearDown();
        }

        // Gating

        // The bar must never render outside execution_type=test: leaking SQL, params,
        // log context and trace ids into a production page is a security regression.
        /** @dataProvider executionTypesOtherThanTest */
        public function testIsSuppressedWhenExecutionTypeIsNotTest(?string $executionType): void {
            $this->bootFramework(is_null($executionType) ? [] : ["execution_type" => $executionType]);

            DebugBarBridge::bootstrap();

            $this->assertFalse(DebugBarBridge::isEnabled());
            $this->assertSame("", DebugBarBridge::renderHead());
            $this->assertSame("", DebugBarBridge::renderBody());
        }

        public static function executionTypesOtherThanTest(): array {
            return [
                "prod" => ["prod"],
                "dev" => ["dev"],
                "empty" => [""],
                "different case" => ["TEST"],
                "not configured" => [null],
            ];
        }

        public function testCollectingIsANoOpWhileTheBarIsSuppressed(): void {
            $this->bootFramework(["execution_type" => "prod"]);
            DebugBarBridge::bootstrap();

            DebugBarBridge::collectQuery("SELECT 1", 0.0, 0, []);
            DebugBarBridge::collectTemplate("core/render", ["data" => "Data"], "blade", "layout/default_layout.php");
            DebugBarBridge::collectResolution("controllers", "CoreController", "userspace", "/app/Controllers/CoreController.php");

            $this->assertFalse(DebugBarBridge::isEnabled());
        }

        // The bridge keeps its state in private statics that no method resets
        private function resetBridge(): void {
            $class = new ReflectionClass(DebugBarBridge::class);
            foreach(["debugBar", "renderer"] as $property) {
                $reflection = $class->getProperty($property);
                $reflection->setAccessible(true);
                $reflection->setValue(null, null);
            }
        }
    }

?>
