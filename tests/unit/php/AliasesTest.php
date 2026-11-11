<?php

    namespace Tests;

    use ReflectionClass;

    class AliasesTest extends TestCase {

        /** @dataProvider aliases */
        public function testResolvesToItsCanonicalFqcn(string $alias, string $canonical): void {
            $this->assertTrue(class_exists($alias), "The alias $alias does not exist");
            $this->assertSame($canonical, (new ReflectionClass($alias))->getName());
        }

        public static function aliases(): array {
            return [
                "z_framework" => ["z_framework", \ZubZet\Framework\ZubZet::class],
                "z_db" => ["z_db", \ZubZet\Framework\Database\Connection::class],
                "z_controller" => ["z_controller", \ZubZet\Framework\Core\Controller::class],
                "z_model" => ["z_model", \ZubZet\Framework\Core\Model::class],
                "RequestResponseHandler" => ["RequestResponseHandler", \ZubZet\Framework\Message\RequestResponseHandler::class],
                "Request" => ["Request", \ZubZet\Framework\Message\Request::class],
                "Response" => ["Response", \ZubZet\Framework\Message\Response::class],
                "z_upload" => ["z_upload", \ZubZet\Framework\Form\Upload::class],
                "FormResult" => ["FormResult", \ZubZet\Framework\Form\Validation\Result::class],
                "FormField" => ["FormField", \ZubZet\Framework\Form\Validation\Field::class],
                "Rest" => ["Rest", \ZubZet\Framework\Support\Rest::class],
                "User" => ["User", \ZubZet\Framework\Authentication\User::class],
                "StaticCache" => [\ZubZet\Framework\Support\StaticCache::class, \ZubZet\Framework\Support\RuntimeCache\RuntimeCache::class],
            ];
        }

        public function testEveryAliasOfTheSourceIsListed(): void {
            $source = file_get_contents(FRAMEWORK_ROOT . "/src/Aliases.php");

            // A new class_alias() needs a data set above, or it goes unchecked
            $this->assertSame(count(self::aliases()), preg_match_all('/^\s*class_alias\(/m', $source));
        }
    }

?>
