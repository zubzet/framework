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

        // configSwitch()

        /** @dataProvider switchValues */
        public function testConfigSwitchReadsEverySpellingOfOnAndOff(mixed $value, bool $expected): void {
            $this->setSettings(["feature" => $value]);

            $this->assertSame($expected, configSwitch("feature", !$expected));
        }

        public static function switchValues(): array {
            // The config file is read with parse_ini_string(), which turns an unquoted switch into "1" or ""
            $ini = fn(string $value) => parse_ini_string("feature = $value")["feature"];

            return [
                "true in the config file" => [$ini("true"), true],
                "on in the config file" => [$ini("on"), true],
                "yes in the config file" => [$ini("yes"), true],
                "false in the config file" => [$ini("false"), false],
                "off in the config file" => [$ini("off"), false],
                "no in the config file" => [$ini("no"), false],
                "none in the config file" => [$ini("none"), false],
                "a quoted true" => [$ini('"true"'), true],
                "a quoted false" => [$ini('"false"'), false],
                "true from an environment variable" => ["true", true],
                "TRUE in capitals" => ["TRUE", true],
                "On" => ["On", true],
                "yes" => ["yes", true],
                "1" => ["1", true],
                "false from an environment variable" => ["false", false],
                "False" => ["False", false],
                "off" => ["off", false],
                "no" => ["no", false],
                "0" => ["0", false],
                "an empty value" => ["", false],
                "surrounding spaces" => [" true ", true],
                "a boolean true" => [true, true],
                "a boolean false" => [false, false],
                "an integer one" => [1, true],
                "an integer zero" => [0, false],
            ];
        }

        public function testConfigSwitchUsesTheDefaultForAMissingKey(): void {
            $this->assertTrue(configSwitch("feature", true));
            $this->assertFalse(configSwitch("feature", false));
        }

        // A typo must not silently switch something off
        /** @dataProvider valuesThatAreNoSwitch */
        public function testConfigSwitchRejectsAValueThatIsNoSwitch(string $value): void {
            $this->setSettings(["feature" => $value]);

            $this->assertThrows(
                InvalidArgumentException::class,
                "Config key 'feature' must be a switch (true or false), got: '$value'",
                fn() => configSwitch("feature", false),
            );
        }

        public static function valuesThatAreNoSwitch(): array {
            return [
                "a typo" => ["ture"],
                "another number" => ["2"],
                "a word" => ["enabled"],
                "a negative number" => ["-1"],
            ];
        }

        public function testConfigSwitchRejectsAListOfValues(): void {
            $this->setSettings(["feature" => ["true"]]);

            $this->assertThrows(
                InvalidArgumentException::class,
                "Config key 'feature' must be a switch (true or false), got: a list",
                fn() => configSwitch("feature", false),
            );
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
