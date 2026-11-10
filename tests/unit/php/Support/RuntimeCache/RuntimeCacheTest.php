<?php

    namespace Tests\Support\RuntimeCache;

    use PHPUnit\Framework\TestCase;
    use ZubZet\Framework\Support\RuntimeCache\InternalRuntimeCache;
    use ZubZet\Framework\Support\RuntimeCache\RuntimeCache;

    class RuntimeCacheTest extends TestCase {

        private string $type;

        // Nothing can be removed from the cache, so every test gets a type of its own
        protected function setUp(): void {
            $this->type = uniqid("test_");
        }

        public function testSetReturnsTheValueAndGetFindsIt(): void {
            $this->assertSame("value", RuntimeCache::set($this->type, "key", "value"));
            $this->assertSame("value", RuntimeCache::get($this->type, "key"));
            $this->assertTrue(RuntimeCache::has($this->type, "key"));
        }

        public function testSetReplacesAValue(): void {
            RuntimeCache::set($this->type, "key", "old");
            RuntimeCache::set($this->type, "key", "new");

            $this->assertSame("new", RuntimeCache::get($this->type, "key"));
        }

        public function testGetThrowsForAnUnknownType(): void {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage("The type '{$this->type}' does not exist in the cache.");

            RuntimeCache::get($this->type, "key");
        }

        public function testGetThrowsForAnUnknownKey(): void {
            RuntimeCache::set($this->type, "other", "value");

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage("The key 'key' does not exist in the cache.");

            RuntimeCache::get($this->type, "key");
        }

        public function testGetOrNullAndHasDoNotThrow(): void {
            $this->assertNull(RuntimeCache::getOrNull($this->type, "key"));
            $this->assertFalse(RuntimeCache::has($this->type, "key"));
        }

        public function testAStoredNullCountsAsNotSet(): void {
            RuntimeCache::set($this->type, "key", null);

            $this->assertFalse(RuntimeCache::has($this->type, "key"));
            $this->assertNull(RuntimeCache::getOrNull($this->type, "key"));
        }

        public function testTheFrameworkValueIsOutOfReachForTheApplication(): void {
            InternalRuntimeCache::set($this->type, "key", "framework");

            $this->assertFalse(RuntimeCache::has($this->type, "key"));
            $this->assertNull(RuntimeCache::getOrNull($this->type, "key"));
        }

        public function testTheApplicationValueIsOutOfReachForTheFramework(): void {
            RuntimeCache::set($this->type, "key", "application");

            $this->assertFalse(InternalRuntimeCache::has($this->type, "key"));
            $this->assertNull(InternalRuntimeCache::getOrNull($this->type, "key"));
        }

        public function testTheSameTypeAndKeyHoldAValueInEachStore(): void {
            RuntimeCache::set($this->type, "key", "application");
            InternalRuntimeCache::set($this->type, "key", "framework");

            $this->assertSame("application", RuntimeCache::get($this->type, "key"));
            $this->assertSame("framework", InternalRuntimeCache::get($this->type, "key"));
        }

        public function testTheFormerClassNameReachesTheSameStore(): void {
            \ZubZet\Framework\Support\StaticCache::set($this->type, "key", "value");

            $this->assertSame("value", RuntimeCache::get($this->type, "key"));
        }

        public function testAnotherSubclassHasAStoreOfItsOwn(): void {
            $moduleCache = new class extends RuntimeCache {};

            $moduleCache::set($this->type, "key", "module");

            $this->assertSame("module", $moduleCache::get($this->type, "key"));
            $this->assertFalse(RuntimeCache::has($this->type, "key"));
            $this->assertFalse(InternalRuntimeCache::has($this->type, "key"));
        }
    }

?>
