<?php

    namespace Tests\Authentication;

    use ReflectionProperty;
    use Tests\TestCase;
    use ZubZet\Framework\Authentication\APIKey;
    use ZubZet\Framework\Authentication\AuthenticationEntity;
    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\Group;
    use ZubZet\Framework\Authentication\Permission\Role;
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Database\DatabaseEntity;

    class AuthenticationEntityTest extends TestCase {

        /** @dataProvider authenticationClasses */
        public function testIsTheBaseOfEveryAuthenticationClass(string $class): void {
            $this->assertTrue(is_subclass_of($class, AuthenticationEntity::class));
        }

        public function testIsADatabaseEntity(): void {
            $this->assertTrue(is_subclass_of(AuthenticationEntity::class, DatabaseEntity::class));
        }

        // User, Role, Group and Organization have to share one permission flag
        /** @dataProvider authenticationClasses */
        public function testDeclaresThePermissionFlagOnce(string $class): void {
            $property = new ReflectionProperty($class, "permissionChanged");

            $this->assertSame(AuthenticationEntity::class, $property->getDeclaringClass()->getName());
        }

        /** @return array<string, array{string}> */
        public function authenticationClasses(): array {
            return [
                "User" => [User::class],
                "Role" => [Role::class],
                "Group" => [Group::class],
                "Organization" => [Organization::class],
                "Session" => [Session::class],
                "APIKey" => [APIKey::class],
            ];
        }
    }

?>
