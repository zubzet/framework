# Access Control

ZubZet now includes significantly enhanced **access control and permission capabilities**.
The system is built to provide a **flexible and extensible authorization workflow**, improved **developer ergonomics**, and full support for **user-based and role-based permissions**.

At its core, the access control system introduces two primary domain objects: **User** and **Role**.
[Permissions](permission-system.md) can be assigned directly to users or indirectly through roles. All permission checks automatically resolve the **combined permission set**.

---

## User Object

The [`User`](../api/classes/ZubZet-Framework-Authentication-Permission-User.html) object represents an application user and exposes a comprehensive API for retrieval, lifecycle management, and permission handling.

### User Retrieval

All retrieval methods are **public static** and return fully hydrated `User` objects.

* Returns all users assigned to the given role.

    ```php
    User::byRole(Role $role): array
    ```

* Returns a single user by email address or `null` if no user exists.

    ```php
    User::byEmail(string $email): ?User
    ```

* Returns all users that were **not verified up to the given date**.

    ```php
    User::byNotVerified(DateTime $since): array
    ```

* Returns all users that have **all** of the specified permissions.
  This includes **user-based permissions and role-based permissions**.

    ```php
    User::byAccessToAll(string ...$permissions): array
    ```

* Returns all users that have **at least one** of the specified permissions.
  This includes **user-based permissions and role-based permissions**.

    ```php
    User::byAccessToAnyOf(string ...$permissions): array
    ```

* Returns all active users assigned to the given organization.

    ```php
    User::byOrganization(Organization $organization): array
    ```

* Returns all users.

    ```php
    User::all(): array
    ```

* Returns a user by its ID or `null` if not found.

    ```php
    User::byId(int|string $id): ?User
    ```

* Returns all users matching the given IDs.

    ```php
    User::byIds(int ...$ids): array
    ```

---

### Loading a User from Database Data

If a user instance needs to be created from an existing database row, the object can be hydrated manually.

* Loads raw database data into a user object.

    ```php
    $user = (new User())->loadObject(array $data);
    ```

---

### Creating a User

Users can be created through the static `add` method.

* Creates a new user.

    ```php
    User::add(?string $email, ?string $password, ?DateTime $verified = null);
    ```

If no verification date is provided, the user is created as **unverified**.

The `$password` parameter is optional. When `null` is passed, the user is created without a password - useful for invite or SSO flows where a credential is set later via `updatePassword()`.

---

### Updating User Data

User attributes can be updated directly on the instance.

* Updates the user’s email address.

    ```php
    $user->updateEmail(?string $email);
    ```

* Updates the user’s [password](password-handling.md).

    ```php
    $user->updatePassword(string $password);
    ```

* Assigns the user to an organization, or unsets it when `null` is passed.
  If the previous and/or the new organization has a linked permission group (see `Organization::getGroup()`), the user's group memberships are kept in sync: the previous organization's group is removed and the new organization's group is added.

    ```php
    $user->updateOrganization(?Organization $organization);
    ```

* Clears all active login sessions for the user.

    ```php
    $user->clearSessions();
    ```

---

### Removing a User

Removing a user performs a **soft delete**.

* Soft deletes the user (`active = 0`).

    ```php
    $user->remove();
    ```

---

### Verification Handling

Verification status is time-aware and can be set or queried at arbitrary points in time.

* Marks the user as verified.
  Defaults to `NOW` if no date is provided.

    ```php
    $user->verify(?DateTime $date = null);
    ```

* Checks whether the user was verified at a specific time.

    ```php
    $user->isVerified(string $at = "NOW"): bool
    ```

---

### Role Assignment

Users can have multiple roles assigned or removed dynamically.

* Adds one or more roles to the user.

    ```php
    $user->rolesAdd(Role ...$roles);
    ```

* Removes one or more roles from the user.

    ```php
    $user->rolesRemove(Role ...$roles);
    ```

---

### User-Based Permissions

Permissions can be assigned directly to users in addition to role-based permissions.

* Adds user-based permissions.

    ```php
    $user->permissionsAdd(string ...$permissionNames);
    ```

* Removes user-based permissions.

    ```php
    $user->permissionsRemove(string ...$permissionNames);
    ```

---

### Permission Checks

Permission checks always resolve the **complete permission set**.

* Checks whether the user has **all** specified permissions.

    ```php
    $user->hasAccessAll(string ...$permissionNames): bool
    ```

* Checks whether the user has **at least one** of the specified permissions.

    ```php
    $user->hasAccessAnyOf(string ...$permissionNames): bool
    ```

---

### Permission Retrieval and Refresh

* Returns only user-based permissions.

    ```php
    $user->getUserPermissions(): array
    ```

* Returns all effective permissions (user-based + role-based).

    ```php
    $user->getPermissions(): array
    ```

* Reloads user-based permissions.

    ```php
    $user->refreshPermissions();
    ```

* Reloads all permissions including roles.

    ```php
    $user->refreshAllPermissions();
    ```

---

### User Data & Utility Methods

* Reloads the user data from the database.

    ```php
    $user->refresh();
    ```

* Returns the user’s email address.

    ```php
    $user->email(): ?string
    ```

* Returns the verification timestamp.

    ```php
    $user->verified(): ?string
    ```

* Returns the user ID.

    ```php
    $user->id(): int|string|null
    ```

* Returns all roles assigned to the user.

    ```php
    $user->getRoles(): array
    ```

* Returns the organization the user belongs to, or `null` if none is assigned.
  The result is cached on the instance - including the `null` case - until `clearFields()` or a write operation invalidates it.

    ```php
    $user->organization(): ?Organization
    ```

* Validates that the instance exists and is not null.

    ```php
    $user->checkInstance(): bool
    ```

---

## Role Object

The [`Role`](../api/classes/ZubZet-Framework-Authentication-Permission-Role.html) object represents a named collection of permissions that can be assigned to users.

### Role Retrieval

* Returns all roles assigned to a user.

    ```php
    Role::byUser(User $user): array
    ```

* Returns a role by its name.

    ```php
    Role::byName(string $name): ?Role
    ```

* Returns all roles that have **all** specified permissions.

    ```php
    Role::byAccessToAll(string ...$permissions): array
    ```

* Returns all roles that have **at least one** of the specified permissions.

    ```php
    Role::byAccessToAnyOf(string ...$permissions): array
    ```

* Returns all roles.

    ```php
    Role::all(): array
    ```

* Returns a role by ID.

    ```php
    Role::byId(int|string $id): ?Role
    ```

* Returns all roles matching the given IDs.

    ```php
    Role::byIds(int ...$ids): array
    ```

---

### Role Creation and Loading

* Creates a new role.

    ```php
    Role::add(string $roleName);
    ```

* Loads a role from database data.

    ```php
    $role = (new Role())->loadObject(array $data);
    ```

---

### Updating and Removing Roles

* Updates the role name.

    ```php
    $role->update(string $newName);
    ```

* Soft deletes the role.

    ```php
    $role->remove();
    ```

---

### Role Data Access

* Returns the role name.

    ```php
    $role->name(): string
    ```

* Returns all users assigned to the role.

    ```php
    $role->getUsers(): array
    ```

* Reloads the role’s users.

    ```php
    $role->refreshUsers();
    ```

---

### Role Permissions

* Returns all permissions assigned to the role.

    ```php
    $role->getPermissions(): array
    ```

* Reloads role permissions.

    ```php
    $role->refreshPermissions();
    ```

* Adds permissions to the role.

    ```php
    $role->permissionsAdd(string ...$permissionNames);
    ```

* Removes permissions from the role.

    ```php
    $role->permissionsRemove(string ...$permissionNames);
    ```

* Replaces this role's permissions with the permissions of another role. The current permissions are removed and the source role's active permissions are copied over.

    ```php
    $role->setPermissionsByRole(Role $role);
    ```

---

### Role Permission Checks

* Checks whether the role has **all** specified permissions.

    ```php
    $role->hasAccessAll(string ...$permissionNames): bool
    ```

* Checks whether the role has **at least one** of the specified permissions.

    ```php
    $role->hasAccessAnyOf(string ...$permissionNames): bool
    ```

---

### Refreshing Role Data

* Reloads the role data from the database.

    ```php
    $role->refresh();
    ```

---

## Organization Object

The [`Organization`](../api/classes/ZubZet-Framework-Authentication-Organization.html) object represents a group of users. Users can belong to at most one organization. An organization has a name (not unique) and is soft-deletable like the other authentication objects.

### Organization Retrieval

* Returns the organization a user belongs to, or `null` if the user has none.
  This is a convenience pass-through to `$user->organization()`.

    ```php
    Organization::byUser(User $user): ?Organization
    ```

* Returns all active organizations matching the given name. Names are not unique, so the result may contain zero, one, or multiple organizations.

    ```php
    Organization::byName(string $name): array
    ```

* Returns all organizations.

    ```php
    Organization::all(): array
    ```

* Returns an organization by its ID, or `null` if not found or inactive.

    ```php
    Organization::byId(int|string $id): ?Organization
    ```

* Returns all organizations matching the given IDs.

    ```php
    Organization::byIds(int ...$ids): array
    ```

---

### Creating an Organization

* Creates a new organization with the given name (which may be `null`).
  When `$createGroup` is `true`, a permission `Group` named `"{$name}_Group"` is created and linked to the organization. The linked group is then available via `$organization->getGroup()` and is used by `User::updateOrganization()` to sync group membership.

    ```php
    Organization::add(?string $name, bool $createGroup = false): Organization
    ```

---

### Updating and Removing Organizations

* Updates the organization's name.

    ```php
    $organization->updateName(string $name);
    ```

* Soft deletes the organization (`active = 0`). Users that referenced it will then return `null` from `$user->organization()`, even though their `organizationId` column is preserved.

    ```php
    $organization->remove();
    ```

---

### Organization Data Access

* Returns the organization's name.

    ```php
    $organization->name(): string
    ```

* Returns all active users assigned to this organization.

    ```php
    $organization->getUsers(): array
    ```

* Reloads the cached users list.

    ```php
    $organization->refreshUsers();
    ```

* Returns the permission `Group` linked to this organization, or `null` if none is linked. The result is cached on the instance until `clearFields()` or a write operation invalidates it.

    ```php
    $organization->getGroup(): ?Group
    ```

* Reloads the cached linked group.

    ```php
    $organization->refreshGroup();
    ```

---

## Session Object

The [`Session`](../api/classes/ZubZet-Framework-Authentication-Session.html) object represents a login session (stored in `z_logintoken`) and provides methods for creation, retrieval, lifetime management, and invalidation.

### Session Retrieval

* Returns a session by its token string, or `null` if not found.

    ```php
    Session::byToken(string $token): ?Session
    ```

* Returns all active sessions for a given user. `$isApiKey` narrows the result: `null` returns every session, `true` only api keys, `false` only interactive logins.

    ```php
    Session::byUser(User $user, ?bool $isApiKey = null): array
    ```

* Returns a session by its ID.

    ```php
    Session::byId(int|string $id): ?Session
    ```

* Returns all sessions matching the given IDs.

    ```php
    Session::byIds(int ...$ids): array
    ```

* Returns all sessions.

    ```php
    Session::all(): array
    ```

---

### Creating a Session

* Creates a new login session for a user. An optional `$userExec` can be passed to create an impersonation session where `$user` is the target user and `$userExec` is the acting. If omitted, both are set to `$user`. The optional `$name` labels the session, which is what keeps api keys apart in a list.

    ```php
    Session::add(User $user, ?User $userExec = null, ?string $name = null): Session
    ```

* Sessions started through the login flow name themselves after the user agent of the request that created them, cut to the 255 characters the column holds. A client that sends no user agent produces an unnamed session. Pass a name to [`$res->loginAs()`](../z-admin/login-as-another-user.md) to override this.

---

### API Keys

An api key is an ordinary session: it carries the same token and authenticates
the same way. Two flags set it apart from an interactive login - one classifies
it, the other exempts it from the login timeout.

```php
$key = Session::add($user, name: "Deployment pipeline");
$key->setApiKey(true);
$key->setPermanent(true);

$key->token(); // the value the client sends as its z_login_token

Session::byUser($user, isApiKey: true);  // the user's api keys
Session::byUser($user, isApiKey: false); // the user's logins
```

* Classifies the session as an api key rather than an interactive login. The flag does not change how the session authenticates.

    ```php
    $session->setApiKey(bool $isApiKey): void
    ```

* Exempts the session from the login timeout, or subjects it to it again. A permanent session never expires and is therefore never invalidated on use, so switching this on revives a session that has expired but not yet been used.

    ```php
    $session->setPermanent(bool $isPermanent): void
    ```

* Names the session, or removes its name when `null` is passed.

    ```php
    $session->setName(?string $name): void
    ```

!!! note "The login cookie has its own lifetime"
    `is_permanent` is a server-side flag. `loginAs()` sets the `z_login_token`
    cookie to expire after `loginTimeoutSeconds` regardless, so a browser drops
    a permanent session's cookie at that point even though the session itself
    lives on. Clients that send the token themselves are unaffected.

---

### Lifetime Management

* Extends the session's lifetime by the given number of seconds on top of the base timeout and any existing extension.

    ```php
    $session->extend(int $seconds): void
    ```

* Sets the total extension time in seconds, replacing any previously set extension.

    ```php
    $session->setExtensionTime(int $seconds): void
    ```

---

### Invalidation

* Immediately invalidates the session, preventing further use.

    ```php
    $session->invalidate(): void
    ```

---

### Expiry Check

* Returns `true` if the session has expired. The expiry is calculated from `created` plus the configured `loginTimeoutSeconds` (defaults to 7 days) plus any `extended_seconds`. A permanent session is never expired.

    ```php
    $session->isExpired(): bool
    ```

---

### Session Data

* Returns the session token string.

    ```php
    $session->token(): string
    ```

* Returns the ID of the user who owns the session.

    ```php
    $session->userId(): int|string
    ```

* Returns the ID of the user being executed as (relevant for impersonation sessions).

    ```php
    $session->userIdExec(): int|string
    ```

* Returns the name of the session, or `null` if it has none.

    ```php
    $session->name(): ?string
    ```

* Returns whether the session is exempt from the login timeout.

    ```php
    $session->isPermanent(): bool
    ```

* Returns whether the session is an api key.

    ```php
    $session->isApiKey(): bool
    ```

* Returns the number of seconds the session has been extended by, or `null` if not extended.

    ```php
    $session->extendedSeconds(): ?int
    ```

* Returns the creation timestamp of the session.

    ```php
    $session->created(): string
    ```

---

### Refreshing Session Data

* Reloads the session data from the database.

    ```php
    $session->refresh(): void
    ```