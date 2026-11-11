<?php

    namespace Tests\QueryBuilder;

    use Cake\Database\ValueBinder;
    use ReflectionClass;
    use Tests\TestCase;
    use ZubZet\Framework\QueryBuilder\ZubZetValueBinder;

    class ZubZetValueBinderTest extends TestCase {

        // CakePHP ValueBinder compatibility

        public function testTheCakeValueBinderClassExists(): void {
            $this->assertTrue(class_exists(ValueBinder::class), "Cake\\Database\\ValueBinder class must exist");
        }

        public function testTheCakeValueBinderStillHasTheMethodsAndTheStoreTheBinderOverrides(): void {
            $reflection = new ReflectionClass(ValueBinder::class);

            $this->assertTrue($reflection->hasMethod("placeholder"), "ValueBinder::placeholder() must exist");
            $this->assertTrue($reflection->hasMethod("bind"), "ValueBinder::bind() must exist");
            $this->assertTrue($reflection->hasMethod("generateManyNamed"), "ValueBinder::generateManyNamed() must exist");
            $this->assertTrue($reflection->hasMethod("bindings"), "ValueBinder::bindings() must exist");
            $this->assertTrue($reflection->hasProperty("_bindings"), "ValueBinder::\$_bindings property must exist");
        }

        public function testPlaceholderTakesExactlyOneTokenAndReturnsAString(): void {
            $method = (new ReflectionClass(ValueBinder::class))->getMethod("placeholder");
            $parameters = $method->getParameters();

            $this->assertCount(1, $parameters, "placeholder() must take exactly 1 param");
            $this->assertSame("token", $parameters[0]->getName());
            $this->assertSame("string", (string) $method->getReturnType());
        }

        public function testBindTakesAtLeastAParamAndAValue(): void {
            $method = (new ReflectionClass(ValueBinder::class))->getMethod("bind");

            $this->assertGreaterThanOrEqual(2, count($method->getParameters()), "bind() must have at least 2 params (\$param, \$value)");
        }

        public function testGenerateManyNamedTakesTheValuesFirstAndReturnsAnArray(): void {
            $method = (new ReflectionClass(ValueBinder::class))->getMethod("generateManyNamed");
            $parameters = $method->getParameters();

            $this->assertSame("values", $parameters[0]->getName());
            $this->assertSame("array", (string) $method->getReturnType());
        }

        public function testBindingsReturnsAnArray(): void {
            $method = (new ReflectionClass(ValueBinder::class))->getMethod("bindings");

            $this->assertSame("array", (string) $method->getReturnType());
        }

        // The override

        public function testEveryPlaceholderIsAQuestionMark(): void {
            $binder = new ZubZetValueBinder();

            $this->assertSame("?", $binder->placeholder("c"));
            $this->assertSame("?", $binder->placeholder(":c0"));
        }

        public function testBindStoresTheValuesInOrderWithAQuestionMarkAndNoNamedKey(): void {
            $binder = new ZubZetValueBinder();

            $binder->bind(":c0", "first", "string");
            $binder->bind(":c1", 2, "integer");

            $this->assertSame([
                ["value" => "first", "type" => "string", "placeholder" => "?"],
                ["value" => 2, "type" => "integer", "placeholder" => "?"],
            ], $binder->bindings());
        }

        public function testGenerateManyNamedReturnsAQuestionMarkPerValueKeepingTheKeys(): void {
            $binder = new ZubZetValueBinder();

            $placeholders = $binder->generateManyNamed(["a" => 1, "b" => 2], "integer");

            $this->assertSame(["a" => "?", "b" => "?"], $placeholders);
            $this->assertSame([
                ["value" => 1, "type" => "integer", "placeholder" => "?"],
                ["value" => 2, "type" => "integer", "placeholder" => "?"],
            ], $binder->bindings());
        }
    }

?>
