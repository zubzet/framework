<?php

    namespace ZubZet\Framework\Authentication;

    use ZubZet\Framework\Authentication\Permission\Group;
    use ZubZet\Framework\Authentication\Permission\Permission;
    use ZubZet\Framework\Authentication\Permission\Role;
    use ZubZet\Framework\Authentication\Permission\User;

    class Organization extends AuthenticationEntity {

        use Permission;
        use RetrievalTrait;
        use HandleTrait;

        public static string $dbTable = "z_organization";
        public static string $dbPermissionsTable = "z_organization_permission";
        public static string $dbPermissionsObjectColumn = "organization";
        public static array $dbExpression = [];

        public function __construct(array $data) {
            parent::__construct($data);
            $this->loadObject($data);
        }

        public function loadObject(array $data) {
            $this->data = $data;
            $this->setField("users", null);
            $this->setField("group", null);
            $this->setField("permissions", null);
        }

        public static function add(?string $name, bool $createGroup = false, ?string $groupName = null): Organization {
            $group = null;
            if($createGroup) {
                $group = Group::add($groupName ?? $name . "_Group");
            }

            $organizationData = model("z_organization")->create($name, $group);
            return new Organization($organizationData);
        }

        /**
         * Get the organization a given user belongs to.
         *
         * Convenience pass-through to {@see User::organization()} so callers can use the
         * symmetric `Organization::byUser($user)` style alongside other `by*` retrievers.
         *
         * @param User $user The user to look up
         * @return Organization|null The organization or null if the user has none
         */
        public static function byUser(User $user): ?Organization {
            return $user->organization();
        }

        public static function byRole(Role $role): array {
            $organizations = model("z_permission")->getOrganizationsByRoleGroup($role);
            return array_map(fn($data) => new Organization($data), $organizations);
        }

        public static function byGroup(Group $group): array {
            return static::byRole($group);
        }

        public static function byAccessToAll(string ...$permissionNames): array {
            $variants = array_map(fn($name) => static::buildPermissionVariants($name), $permissionNames);
            $organizations = model("z_permission")->getOrganizationsByAccessToAll($variants);
            return array_map(fn($data) => new Organization($data), $organizations);
        }

        public static function byAccessToAnyOf(string ...$permissionNames): array {
            $variants = array_map(fn($name) => static::buildPermissionVariants($name), $permissionNames);
            $organizations = model("z_permission")->getOrganizationsByAccessToAnyOf(array_merge(...$variants));
            return array_map(fn($data) => new Organization($data), $organizations);
        }

        /**
         * Get all active organizations matching the given name.
         *
         * Organization names are not unique, so this returns an array - possibly empty,
         * possibly containing multiple organizations sharing the same name.
         *
         * @param string $name The name to match exactly
         * @return Organization[] All active organizations with the given name
         */
        public static function byName(string $name): array {
            $organizations = [];
            $organizationDataList = model("z_organization")->byName($name);

            foreach($organizationDataList as $organizationData) {
                $organizations[] = new Organization($organizationData);
            }

            return $organizations;
        }

        public function updateName(string $name): void {
            model("z_organization")->updateName($this, $name);

            $this->setField("name", $name);
        }

        public function name(): string {
            return $this->getField("name");
        }

        public function hasMember(User $user): bool {
            return $user->isMemberOf($this);
        }

        public function getUsers(): array {
            if(is_null($this->getField("users"))) $this->refreshUsers();

            return $this->getField("users");
        }

        public function refreshUsers(): void {
            $users = model("z_user")->getUsersByOrganization($this);
            $this->setField("users", $users);
        }

        public function getGroup(): ?Group {
            if(is_null($this->getField("group"))) $this->refreshGroup();

            return $this->getField("group");
        }

        public function refreshGroup(): void {
            $groupId = $this->getField("groupId");
            if(is_null($groupId)) return;

            $group = Group::byId($groupId);
            $this->setField("group", $group);
        }

        public function rolesAdd(Role ...$roles): void {
            self::$permissionChanged = true;
            model("z_permission")->addRolesGroupsToOrganization($this, ...$roles);
        }

        public function groupsAdd(Group ...$groups): void {
            $this->rolesAdd(...$groups);
        }

        public function rolesRemove(Role ...$roles): void {
            self::$permissionChanged = true;
            model("z_permission")->removeRolesGroupsFromOrganization($this, ...$roles);
        }

        public function groupsRemove(Group ...$groups): void {
            $this->rolesRemove(...$groups);
        }

        public function getRoles(): array {
            return Role::byOrganization($this);
        }

        public function getGroups(): array {
            return Group::byOrganization($this);
        }

        // Own permissions plus those of its roles and groups
        public function getPermissions(): array {
            if(is_null($this->getField("permissions")) || self::$permissionChanged) $this->refreshPermissions();

            return $this->getField("permissions");
        }

        public function refreshPermissions(): void {
            $permissions = model("z_permission")->getPermissionsByOrganizationAll($this);
            $this->setField("permissions", array_column($permissions, "name"));
        }

        public function remove(): void {
            self::$permissionChanged = true;

            // Detach the members first, their group only resolves while the organization is active
            foreach($this->getUsers() as $user) $user->updateOrganization(null);

            $this->getGroup()?->remove();

            model("z_organization")->remove($this);
        }
    }

?>
