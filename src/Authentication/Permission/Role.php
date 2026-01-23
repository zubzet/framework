<?php

namespace ZubZet\Framework\Authentication\Permission;

class Role extends AuthenticationObject {

    // Traits
    use Permission;
    use RetrievalTrait;
    use HandleTrait;

    // Database names
    public static string $dbTable = "z_role";
    public static string $dbPermissionsTable = "z_role_permission";
    public static string $dbPermissionsObjectColumn = "role";

    // Constructor which requires the Role data (in the format of a database row)
    public function __construct(array $data) {
        parent::__construct($data);
        $this->loadObject($data);
    }

    /**
     * Get a list of Roles by User
     *
     * @param User $user The user to filter roles by
     * @return Role[] An array of Role objects associated with the specified user
     */
    public static function byUser(User $user): array {
        return model("z_permission")->getRolesByUsers($user);
    }

    /**
     * Get a Role by its name
     *
     * @param string $name The name of the role
     * @return Role|null The Role object if found, null otherwise
     */
    public static function byName(string $name): ?Role {
        return model("z_permission")->getRoleByName($name);
    }

    public static function byAccessToAll(string ...$permissionNames): array {
        $extractedPermissionsGroup = [];

        foreach($permissionNames as $permissionName) {
            $extractedPermissionsGroup[] = static::buildPermissionVariants($permissionName);
        }

        return model("z_permission")->getRolesByAccessToAll($extractedPermissionsGroup);
    }

    public static function byAccessToAnyOf(string ...$permissionNames): array {
        $extractedPermissions = [];

        foreach($permissionNames as $permissionName) {
            $extractedPermissions = array_merge($extractedPermissions, static::buildPermissionVariants($permissionName));
        }

        return model("z_permission")->getRolesByAccessToAnyOf($extractedPermissions);
    }

    /**
     * Re-/Loads the Role Object
     *
     * @param array $data The data to load into the Role object (in the format of a database row)
     * @return void
     */
    public function loadObject(array $data) {
        $this->data = $data;
        $this->setField("permissions", null);
        $this->setField("users", null);
    }


    /**
     * Create a new Role
     * This will insert the role into the database and return the created Role object
     *
     * @param string $rolename The name of the new role
     * @return Role The created Role object
     */
    public static function add(string $rolename): Role {
        $roleData = model("z_permission")->addRole($rolename);

        return new static($roleData);
    }

    /**
     * Rename the Role
     *
     * @param string $newName The new name for the role
     * @return void
     */
    public function update(string $newName): void {
        model("z_permission")->updateRole($this, $newName);
        $this->clearFields();
    }

    /**
     * Remove the Role
     * This will deactivate the Role in the Database
     *
     * @return void
     */
    public function remove(): void {
        global $permissionChanged;
        $permissionChanged = true;
        model("z_permission")->removeRole($this);

        $this->clearFields();
        $this->nullId();
    }

    /**
     * Get the Roles name from the cache
     *
     * @return string The name of the Role
     */
    public function name(): string {
        return $this->getField('name');
    }

    /**
     * Get Users with this Role
     *
     * @return User[] The users with this role
     */
    public function getUsers(): array {
        if(is_null($this->getField("users"))) $this->refreshUsers();

        return $this->getField("users");
    }

    /**
     * Reset the Users Cache
     *
     * @return void
     */
    private function refreshUsers() {
        $this->setField("users", User::byRole($this));
    }

    /**
     * Get Permissions which are associated with this Role
     *
     * @return string[] The permissions associated with this role
     */
    public function getPermissions(): array {
        // Check if permissions data is changed.
        // This is necessary to ensure that any updates to permissions are reflected
        global $permissionChanged;

        // Refresh permissions if they are null or if there has been a change
        if(is_null($this->getField("permissions")) || $permissionChanged) $this->refreshPermissions();

        return $this->getField("permissions");
    }

    /**
     * Reset the Permissions Cache
     *
     * @return void
     */
    public function refreshPermissions() {
        $this->setField("permissions", model("z_permission")->getPermissionsByRole($this));
    }


}
