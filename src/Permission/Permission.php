<?php

namespace ZubZet\Framework\Permission;

trait Permission {

    // Indicate if permissions have changed globally
    // If true, permissions will be reloaded for each access
    protected static $permissionChanged = false;

    /**
     * Add permissions to an permission object (user or role)
     *
     * @param string[] $permissionNames The names of the permissions to add
     * @return void
     */
    public function permissionsAdd(string ...$permissionNames): void {
        self::$permissionChanged = true;

        model("z_permission")->addPermissionsToObject($this->id(), static::$dbPermissionsTable, static::$dbPermissionsObjectColumn, ...$permissionNames);
        $this->setField("permissions", null);
    }

    /**
     * Remove permissions from the permission object (user or role)
     *
     * @param string[] $permissionNames The names of the permissions to remove
     * @return void
     */
    public function permissionsRemove(string ...$permissionNames): void {
        self::$permissionChanged = true;

        model("z_permission")->removePermissionsFromObject($this->id(), static::$dbPermissionsTable, static::$dbPermissionsObjectColumn, ...$permissionNames);
        $this->setField("permissions", null);
    }

    /**
     * Check if the permission object (user or role) has all of the given permissions
     *
     * @param string[] $permissionNames The names of the permissions to check
     * @return bool True if the object has all of the permissions, false otherwise
     */
    public function hasAccessAll(string ...$permissionNames): bool {
        $this->refreshPermissions();

        $permissions = $this->getPermissions();

        if(!empty($permissions) && is_array($permissions[0])) {
            $permissions = array_column($permissions, 'name');
        }

        $gotPermissions = array_flip($permissions);

        foreach ($permissionNames as $permissionName) {
            $variants = static::buildPermissionVariants($permissionName);

            $hasMatch = false;

            foreach($variants as $variant) {
                if(isset($gotPermissions[$variant])) {
                    $hasMatch = true;
                    break;
                }
            }

            if(!$hasMatch) {
                return false;
            }
        }

        return true;
    }


    /**
     * Check if the permission object (user or role) has one of the permissions
     *
     * @param string[] $permissionNames The names of the permissions to check
     * @return bool True if the object has at least one of the permissions, false otherwise
     */
    public function hasAccessAnyOf(string ...$permissionNames): bool {
        $this->refreshPermissions();

        $permissions = $this->getPermissions();

        if(!empty($permissions) && is_array($permissions[0])) {
            $permissions = array_column($permissions, 'name');
        }

        $gotPermissions = array_flip($permissions);

        foreach($permissionNames as $permissionName) {
            $variants = static::buildPermissionVariants($permissionName);

            foreach($variants as $variant) {
                if (isset($gotPermissions[$variant])) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Build permission variants for a given permission name
     *
     * e.g. "edit.article" => ["*.*", "edit.*", "edit.article.*", "edit.article"]
     *
     * @param string $permissionName The permission name to build variants for
     * @return string[] An array of permission variants
     */
    public static function buildPermissionVariants(string $permissionName): array {
        $parts = explode('.', $permissionName);
        $perm = '';
        $variants = ['*.*', $permissionName];

        foreach($parts as $part) {
            $perm .= $part . '.';
            $variants[] = rtrim($perm, '.') . '.*';
        }

        return array_unique($variants);
    }

}
