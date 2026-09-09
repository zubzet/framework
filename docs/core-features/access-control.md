# Access Control

ZubZet now includes significantly enhanced **access control and permission capabilities**.
The system is built to provide a **flexible and extensible authorization workflow**, improved **developer ergonomics**, and full support for **user-based and role-based permissions**.

At its core, the access control system introduces two primary domain objects: **User** and **Role**.
[Permissions](permission-system.md) can be assigned directly to users or indirectly through roles. All permission checks automatically resolve the **combined permission set**.

---

## UUIDs

Every user, role, group, organization, session and API key carries a **UUID** next to its numeric ID,
stored in the `uuid` column as a native MariaDB `UUID` and read back as an RFC 4122 string. It is the identifier meant to leave the
application: URLs, REST payloads and anything handed to a third party. Unlike the sequential ID, it
does not reveal how many records exist and cannot be guessed by counting up. The numeric ID stays the
internal key used for foreign keys and joins.

Every object that can be retrieved by ID can also be retrieved by UUID, and every object returns its own:

```php
User::byUuid(string $uuid): ?User
Role::byUuids(string ...$uuids): array
$organization->uuid(): string
```

The UUID of the authenticated user is available without a lookup:

```php
user()->uuid;
```

A [session](#session-object) or [API key](#api-keys) has a UUID as well, and it is the only one of
its identifiers that is safe to hand out: the token authenticates whoever presents it, so it belongs
in a cookie or an `Authorization` header and nowhere else. Use the UUID to name a session in a "your
active sessions" list or to address one for renaming or invalidation. Like `byId()`, `byUuid()` is
scoped to the class it is called on - `Session::byUuid()` does not find an API key and
`APIKey::byUuid()` does not find a login.

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

* Returns a user by its [UUID](#uuids) or `null` if not found.

    ```php
    User::byUuid(string $uuid): ?User
    ```

* Returns all users matching the given UUIDs.

    ```php
    User::byUuids(string ...$uuids): array
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

* Returns the user's [UUID](#uuids).

    ```php
    $user->uuid(): string
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

* Returns a role by its [UUID](#uuids) or `null` if not found.

    ```php
    Role::byUuid(string $uuid): ?Role
    ```

* Returns all roles matching the given UUIDs.

    ```php
    Role::byUuids(string ...$uuids): array
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

* Returns the role's [UUID](#uuids).

    ```php
    $role->uuid(): string
    ```

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

* Returns an organization by its [UUID](#uuids), or `null` if not found or inactive.

    ```php
    Organization::byUuid(string $uuid): ?Organization
    ```

* Returns all organizations matching the given UUIDs.

    ```php
    Organization::byUuids(string ...$uuids): array
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

* Returns the organization's [UUID](#uuids).

    ```php
    $organization->uuid(): string
    ```

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

The [`Session`](../api/classes/ZubZet-Framework-Authentication-Session.html) object represents a login session (stored in `z_logintoken`) and provides methods for creation, retrieval, lifetime management, and invalidation. An api key is the same row with `is_apikey` set, represented by [`APIKey`](#api-keys). The two are siblings, not parent and child: they get their behavior from the `CanUseSession` trait, so everything documented here applies to both. Type as `Session|APIKey` where either kind may turn up.

### Session Retrieval

* Returns a session by its token string, or `null` if not found. The row decides the class, so a token belonging to an api key returns an [`APIKey`](#api-keys). This is the entry point of the authentication and therefore resolves both kinds, whichever class it is called on.

    ```php
    Session::byToken(string $token): Session|APIKey|null
    ```

* Returns all active logins of a user. Api keys are listed by `APIKey::byUser()` instead.

    ```php
    Session::byUser(User $user): array
    ```

* Returns a login by its ID, or `null` if the ID belongs to an api key.

    ```php
    Session::byId(int|string $id): ?Session
    ```

* Returns all logins matching the given IDs.

    ```php
    Session::byIds(int ...$ids): array
    ```

* Returns a login by its [UUID](#uuids), or `null` if the UUID belongs to an api key. This is the identifier to use when a session has to be addressed from outside the application - the token must not leave the client.

    ```php
    Session::byUuid(string $uuid): ?Session
    ```

* Returns all logins matching the given UUIDs.

    ```php
    Session::byUuids(string ...$uuids): array
    ```

* Returns all logins.

    ```php
    Session::all(): array
    ```

---

### Creating a Session

* Creates a new login session for a user. An optional `$userExec` can be passed to create an impersonation session where `$user` is the target user and `$userExec` is the acting. If omitted, both are set to `$user`. The optional `$name` labels the session, which is what a "your active sessions" list shows, and `$reason` records why it was created.

    ```php
    Session::add(User $user, ?User $userExec = null, ?string $name = null, ?string $reason = null): Session
    ```

* Names the session, or removes its name when `null` is passed.

    ```php
    $session->setName(?string $name): void
    ```

* Every session records the request that created it, without the caller doing anything: `device` holds the user agent (cut to the 255 characters the column takes, `null` when none was sent) and `ip_creation` the address it was created from. A name is never derived from either - it stays `null` until someone chooses one.

---

### API Keys

An api key is a session that is handed out deliberately instead of being
created by logging in. It carries the same token, authenticates the same way and
has the same lifetime controls -
[`APIKey`](../api/classes/ZubZet-Framework-Authentication-APIKey.html) shares all
of that with `Session` through the `CanUseSession` trait. What differs is that a
key belongs to a single user and has **no executing user**, so its `add()` takes
no `$userExec`. That is why the two are siblings rather than one extending the
other: PHP would not allow the narrower signature on a subclass.

```php
$key = APIKey::add($user, name: "Deployment pipeline");
$key->setCanExpire(false); // from CanUseSession, like setName() and extend()

$key->token(); // the value the client sends as its z_login_token

APIKey::byUser($user);  // the user's api keys
Session::byUser($user); // the user's logins
```

Every retriever is scoped to the class it is called on: `Session::byId()` does
not find an api key and `APIKey::byId()` does not find a login, the same way
[`Role`](#role-object) and `Group` split the `z_role` table. The same holds for
`APIKey::byUuid()` and `APIKey::byUuids()`, which are the safe way to address a
key from outside the application - unlike the token, a [UUID](#uuids) grants
nothing to whoever reads it.

`byToken()` is the one exception. It resolves both kinds whichever class it is
called on - returning an `APIKey` for a key and a plain `Session` for a login -
so an authenticating request is judged by the right rules without the caller
having to know which kind the token is.

* Creates an api key for a user. The name is what keeps keys apart in a list, so it is worth passing.

    ```php
    APIKey::add(User $user, ?string $name = null, ?string $reason = null): APIKey
    ```

Everything else a key needs it already has as a session: it is named through
[`setName()`](#creating-a-session) and exempted from the login timeout through
[`setCanExpire(false)`](#lifetime-management), which is what most keys want.

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

* Fixes the point in time the session expires, or hands it back to the computed lifetime when `null` is passed. A fixed expiry replaces that lifetime entirely: neither `loginTimeoutSeconds` nor `extended_seconds` has a say while one is set, so `extend()` and `setExtensionTime()` write an extension that does not move the expiry. Takes a `DateTime`, like [`$user->verify()`](#verification-handling) does; the getters stay strings. The column is a `TIMESTAMP`, so the expiry has to fall inside its range - from 1970 up to `2106-02-07 06:28:15` UTC on the supported MariaDB versions. A date outside it is rejected by the database rather than silently truncated.

    ```php
    $session->setExpiresAt(?DateTime $expiresAt): void
    ```

    ```php
    $session->setExpiresAt(new DateTime("+30 days"));
    $session->setExpiresAt(null); // back to the computed lifetime
    ```

* Subjects the session to expiring, or exempts it from it. A session passed `false` never expires and is therefore never invalidated on use, so exempting one revives a session that has expired but not yet been used. This is what turns an [api key](#api-keys) into a credential that keeps working, but it applies to any session. The exemption outranks a fixed expiry - it takes the session out of expiring altogether, rather than moving the point at which it does. It is stored inverted, in the `is_permanent` column.

    ```php
    $session->setCanExpire(bool $canExpire): void
    ```

!!! note "The login cookie has its own lifetime"
    `is_permanent` and `expires_at` are server-side. `loginAs()` sets the
    `z_login_token` cookie to expire after `loginTimeoutSeconds` regardless, so a
    browser drops the cookie at that point even though the session itself lives
    on. Clients that send the token themselves are unaffected.

---

### Invalidation

* Immediately invalidates the session, preventing further use.

    ```php
    $session->invalidate(): void
    ```

---

### Expiry Check

* Returns `true` if the session has expired. A session exempt from expiring never is; otherwise the expiry is the one [fixed on the session](#lifetime-management), or, when it carries none, `created` plus the configured `loginTimeoutSeconds` (defaults to 7 days) plus any `extended_seconds`.

    ```php
    $session->isExpired(): bool
    ```

* Returns the point in time the session becomes unusable, or `null` when the session is exempt from expiring. This is the effective expiry, whether it was fixed through [`setExpiresAt()`](#lifetime-management) or computed from the lifetime.

    ```php
    $session->expiresAt(): ?string
    ```

---

### Session Data

* Returns the session's [UUID](#uuids). It is the only identifier of a session that is safe to hand out.

    ```php
    $session->uuid(): string
    ```

* Returns the session token string. Issued tokens are a `zub-` prefix plus 32 random bytes in hex, 68 characters in total. Tokens issued before this format keep working, so do not validate one by its length.

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

* Returns the user agent the session was started from, or `null` if none was sent.

    ```php
    $session->device(): ?string
    ```

* Returns why the session was created, or `null` if no reason was given.

    ```php
    $session->reason(): ?string
    ```

* Returns the address the session was created from, or `null` if none could be determined.

    ```php
    $session->ipCreation(): ?string
    ```

* Returns the address the session was last used from, or `null` while it has not been used. It is rewritten on the first authenticated request that arrives from a different address, so a session that never moves is never written to.

    ```php
    $session->ipLast(): ?string
    ```

* Returns whether the session is subject to expiring at all. `false` means it is exempt, which is what [`setCanExpire(false)`](#lifetime-management) does.

    ```php
    $session->canExpire(): bool
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