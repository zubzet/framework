<?php

namespace ZubZet\Framework\Authentication;

use ZubZet\Framework\Database\DatabaseEntity;

/** @internal */
class AuthenticationEntity extends DatabaseEntity {

    /**
     * Process-wide flag indicating that permission-related data has changed.
     *
     * Declared once on this class (and never redeclared in the trait or
     * subclasses) so that User, Role, Group and Organization all share a single
     * storage slot. When true, cached permissions are reloaded on each access,
     * regardless of which entity triggered the change.
     *
     * @var bool
     */
    protected static bool $permissionChanged = false;
}
