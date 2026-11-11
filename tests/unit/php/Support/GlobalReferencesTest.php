<?php

    namespace Tests\Support;

    use InvalidArgumentException;
    use Tests\TestCase;
    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\ErrorHandling\GenericException\NotInstantiatedException;
    use ZubZet\Framework\ZubZet;

    class GlobalReferencesTest extends TestCase {

        private const ERROR_NO_INSTANCE = "The requested instance 'ZubZet (The framework itself)' has not yet been setup.";
        private const ERROR_NO_REQUEST = "The requested instance 'Request' has not yet been setup.";
        private const ERROR_NO_CONNECTION = "The requested instance 'Connection (Database)' has not yet been setup.";
        private const ERROR_ONLY_DEFAULT = "Only the default connection is supported so far.";

        protected function setUp(): void {
            $this->bootFramework(["custom_value" => "TestValue", "rootDirectory" => ""]);
        }

        // References

        public function testZubzetResolvesTheHelperFunction(): void {
            $this->assertSame(ZubZet::$instance, zubzet());
            $this->assertSame("TestValue", zubzet()->custom_value);
        }

        public function testRequestResolvesTheHelperFunction(): void {
            $request = $this->request($this->input()->withUrl("http://localhost/helper/request/test"));

            $this->assertSame($request, request());
            $this->assertSame("test", request()->getParameters(0, 1));
        }

        public function testRequestThrowsWhenNoRequestExists(): void {
            $this->assertThrows(NotInstantiatedException::class, self::ERROR_NO_REQUEST, fn() => request());
        }

        public function testResponseResolvesTheHelperFunction(): void {
            $response = $this->response();

            $this->assertSame($response, response());
        }

        public function testConfigResolvesTheHelperFunction(): void {
            $this->assertSame("TestValue", config("custom_value"));
        }

        public function testConfigWithoutAKeyReturnsEverySetting(): void {
            $this->assertSame(["custom_value" => "TestValue", "rootDirectory" => ""], config());
            $this->assertSame(zubzet()->getAllAttributes(), config(""));
        }

        public function testConfigReturnsTheDefaultForAMissingKey(): void {
            $this->assertNull(config("missing"));
            $this->assertSame("fallback", config("missing", default: "fallback"));
        }

        public function testConfigThrowsForAMissingKeyWithoutADefault(): void {
            $this->assertThrows(InvalidArgumentException::class, "The setting 'missing' does not exist!", fn() => config("missing", false));
        }

        public function testConfigNumericCastsANumericSettingToAnInteger(): void {
            $this->setSettings(["limit" => "12"]);

            $this->assertSame(12, configNumeric("limit", 5));
        }

        public function testConfigNumericUsesTheDefaultForAMissingKey(): void {
            $this->assertSame(5, configNumeric("limit", 5));
        }

        /** @dataProvider nonNumericValues */
        public function testConfigNumericRejectsANonNumericSetting(string $value): void {
            $this->setSettings(["limit" => $value]);

            $this->assertThrows(InvalidArgumentException::class, "Config key 'limit' must be numeric, got: '$value'", fn() => configNumeric("limit", 5));
        }

        public static function nonNumericValues(): array {
            return [
                "off" => ["off"],
                "letters" => ["abc"],
                "an empty string" => [""],
            ];
        }

        public function testUserResolvesTheHelperFunction(): void {
            // Without a token the user is identified as a guest, without the database
            $this->request();
            zubzet()->user = new User;

            $this->assertSame(zubzet()->user, user());
            $this->assertFalse(user()->isLoggedIn);
        }

        public function testUserIsNullBeforeTheFrameworkHasLoadedIt(): void {
            $this->assertNull(user());
        }

        public function testDbResolvesTheHelperFunction(): void {
            zubzet()->z_db = new Connection;

            $this->assertSame(zubzet()->z_db, db());
            $this->assertNotNull(db()->queryBuilderConnection);
        }

        // Boot-state branches

        public function testZubzetThrowsNotInstantiatedExceptionWhenInstanceIsUnset(): void {
            ZubZet::$instance = null;

            $this->assertThrows(NotInstantiatedException::class, self::ERROR_NO_INSTANCE, fn() => zubzet());
        }

        public function testDbRejectsNonDefaultConnectionKeys(): void {
            $this->assertThrows(InvalidArgumentException::class, self::ERROR_ONLY_DEFAULT, fn() => db("custom-not-default"));
        }

        public function testDbRejectsNonDefaultConnectionKeysEvenWhenAnUnsetConnectionIsAllowed(): void {
            unset(zubzet()->z_db);

            $this->assertThrows(InvalidArgumentException::class, self::ERROR_ONLY_DEFAULT, fn() => db("custom-not-default", allowUnsetConnection: true));
        }

        public function testDbDefaultAllowUnsetConnectionReturnsNullWhenZDbIsUnset(): void {
            unset(zubzet()->z_db);

            $this->assertNull(db("default", allowUnsetConnection: true));
        }

        public function testDbThrowsNotInstantiatedExceptionWhenZDbIsNotAConnection(): void {
            zubzet()->z_db = new \stdClass();

            $this->assertThrows(NotInstantiatedException::class, self::ERROR_NO_CONNECTION, fn() => db());
        }

        public function testDbThrowsNotInstantiatedExceptionWhenNoConnectionWasSet(): void {
            $this->assertThrows(NotInstantiatedException::class, self::ERROR_NO_CONNECTION, fn() => db());
        }

        public function testDbThrowsForAnObjectThatIsNoConnectionEvenWhenAnUnsetConnectionIsAllowed(): void {
            zubzet()->z_db = new \stdClass();

            $this->assertThrows(NotInstantiatedException::class, self::ERROR_NO_CONNECTION, fn() => db("default", allowUnsetConnection: true));
        }
    }

?>
