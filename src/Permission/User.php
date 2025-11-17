<?php

namespace ZubZet\Framework\Permission;

use DateTime;

class User extends AuthenticationObject {

    // Traits
    use Permission;
    use RetrievalTrait;
    use HandleTrait;


    // Database names
    public static string $dbTable = "z_user";
    public static string $dbPermissionsTable = "z_user_permission";
    public static string $dbPermissionsObjectColumn = "user";


    // Constructor which requires the Users data (in the format of a database row)
    public function __construct(array $data) {
        parent::__construct($data);
        $this->loadObject($data);
    }


    /**
     * Get a list of users by a specific role
     *
     * @param Role $role The role to filter users by
     * @return User[] An array of User objects associated with the specified role
     */
    public static function byRole(Role $role): array {
        return model("z_permission")->getUsersByRole($role);
    }

    /**
     * Get a user by their email address
     *
     * @param string $email The email address of the user
     * @return User|null The User object if found, null otherwise
     */
    public static function byEmail(string $email): ?User {
        return model("z_permission")->getUserByEmail($email);
    }

    /**
     * Get a list of users who were not verified as of the $since DateTime
     *
     * @param \DateTime $since When to check for non-verified users
     * @return User[] array of user objects
     */
    public static function byNotVerified(DateTime $since): array {
        return model("z_permission")->getNotVerifiedUsers($since);
    }

    public static function byAccessToAll(string ...$permissionNames): array {
        $extractedPermissionsGroup = [];

        // Build permission variants for each permission name to check them
        // e.g. "edit.article" => ["*.*", "edit.*", "edit.article.*", "edit.article"]
        // This results in a 2D array of permission sets (e.g. [ ["*.*", "edit.*"], ["*.*", "post.*"] ])
        foreach($permissionNames as $permissionName) {
            $extractedPermissionsGroup[] = static::buildPermissionVariants($permissionName);
        }

        return model("z_permission")->getUsersByAccessToAll($extractedPermissionsGroup);
    }

    public static function byAccessToAnyOf(string ...$permissionNames): array {
        $extractedPermissions = [];

        // Build permission variants for each permission name to check them
        // e.g. "edit.article" => ["*.*", "edit.*", "edit.article.*", "edit.article"]
        foreach($permissionNames as $permissionName) {
            $extractedPermissions = array_merge($extractedPermissions, static::buildPermissionVariants($permissionName));
        }

        return model("z_permission")->getUsersByAccessToAnyOf($extractedPermissions);
    }

    /**
     * Re-/Loads the user object
     *
     * @param array $data The data to load into the user object (in the format of a database row)
     * @return void
     */
    public function loadObject(array $data): void {
        $this->data = $data;
        $this->setField("permissions", null);
        $this->setField("roles", null);
    }


    /**
     * Create a new user
     * This will insert the user into the database and return the created user object
     *
     * @param string $email The email of the new user
     * @param string $password The password of the new user (plain text, will be hashed)
     * @param ?DateTime $verified Optional datetime object indicating when the user was verified
     * @return User|null The created user object or null on failure
     */
    public static function add(?string $email, string $password, ?DateTime $verified = null): ?User {
        // If a datetime object is provided, format it to a string
        if(!is_null($verified)) $verified = $verified->format("Y-m-d H:i:s");

        $insertedId = model("z_user")->add($email, $password, $verified);

        return self::byId($insertedId);
    }

    /**
     * Update the email address of the user
     * This will update the email in the database
     *
     * @param string $email The new email address
     * @return void
     */
    public function updateEmail(?string $email): void {
        model('z_permission')->updateUserEmail($this, $email);
        $this->clearFields();
    }

    /**
     * Update the password of the user
     * This will update the password in the database
     *
     * @param string $password The new password
     * @return void
     */
    public function updatePassword(string $password): void {
        model('z_login')->updatePassword($this->id(), $password);
        $this->clearFields();
    }

    /**
     * Deactivate this user
     *
     * @return void
     */
    public function remove(): void {
        // Setting permission changed to true to indicate permissions need to be reloaded
        global $permissionChanged;
        $permissionChanged = true;

        model("z_permission")->removeUser($this);
        $this->clearFields();
        $this->nullId();
    }

    /**
     * Verify the User
     *
     * @param ?DateTime $date Optional datetime object indicating when the user was verified. If null, current time will be used.
     * @return void
     */
    public function verify(?DateTime $date = null): void {
        if(is_null($date)) $date = new DateTime();

        model("z_permission")->verifyUser($this, $date);
        $this->clearFields();
    }

    /**
     * Add roles to the user
     * This will add the roles automatically into the database
     *
     * @param Role[] $roles The roles to add to the user
     * @return void
     */
    public function rolesAdd(Role ...$roles): void {
        // Setting permission changed to true to indicate permissions need to be reloaded
        global $permissionChanged;
        $permissionChanged = true;

        // Add roles to user in the database
        model("z_permission")->addRolesToUser($this, ...$roles);

        // Clear the roles cache for this user
        $this->clearFields();
    }

    /**
     * Remove roles from the user
     * This will remove the roles automatically from the database
     *
     * @param Role[] $roles The roles to remove from the user
     * @return void
     */
    public function rolesRemove(Role ...$roles): void {
        // Setting permission changed to true to indicate permissions need to be reloaded
        global $permissionChanged;
        $permissionChanged = true;

        // Remove roles from user in the database
        model("z_permission")->removeRolesFromUser($this, ...$roles);

        // Clear the roles cache for this user
        $this->clearFields();
    }

    /**
     * Get the user's email from the cache
     *
     * @return string The email of the user
     */
    public function email(): string {
        return $this->getField('email');
    }

    /**
     * Get the users` verfied date
     * 
     * @return null|string The users verified date or null if not verified
     */
    public function verified(): ?string {
        return $this->getField('verified');
    }

    /**
     * Check if a User is verified
     *
     * @param string $at When to check verification status (default: "NOW")
     * @return bool Returns true if the user is verified at the specified time, false otherwise
     */
    public function isVerified(string $at = "NOW"): bool {
        $verified = $this->getField('verified');

        // If not verified at all return false
        if($verified === null) return false;

        // Parse times
        $verifiedTime = strtotime($verified);
        $atTime = $at === "NOW" ? time() :strtotime($at);

        // Compare times
        return $verifiedTime <= $atTime;
    }

    /**
     * Get the user's roles
     *
     * @return Role[] Array of role objects
     */
    public function getRoles(): array {
        // Load roles if not already loaded
        if(is_null($this->getField("roles"))) $this->setField("roles", Role::byUser($this));

        return $this->getField("roles");
    }

    /**
     * Get the user's permissions
     *
     * @return string[] Array of permissions
     */
    public function getPermissions(): array {
        global $permissionChanged;

        if(is_null($this->getField("permissions")) || $permissionChanged) $this->refreshPermissions();

        return $this->getField("permissions");
    }

    /**
     * Reset the permissions cache
     *
     * @return void
     */
    public function refreshPermissions(): void {
        $this->setField("permissions", model("z_permission")->getPermissionsByUser($this));
    }
}
