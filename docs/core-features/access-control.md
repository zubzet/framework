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

The [`User`](../api/classes/ZubZet-Framework-Authentication-Permission-User.html) object represents an application user and exposes a comprehensive API for retrieval, lifecycle management, and permission handling. Import it with `use ZubZet\Framework\Authentication\Permission\User;`: the global alias `User` is the [request user](global-helper-functions.md#user) instead, and `Role`, `Group` and `Organization` have no alias either.

### User Retrieval

All retrieval methods are **public static** and return fully hydrated `User` objects. They only find active records; `all()` and `byId()` of `Role`, `Group` and `Organization` work the same way.

* Returns all active users with an active assignment to the given role. `User::byGroup(Group $group)` does the same for a group.

    ```php
    User::byRole(Role $role): array
    ```

* Returns the active user with that email address or `null`. Users without an email are never matched.

    ```php
    User::byEmail(string $email): ?User
    ```

* Returns all active users that were **not verified up to the given date**: `verified` is `NULL` or later than it.

    ```php
    User::byNotVerified(DateTime $since): array
    ```

* Returns all active users that have **all** of the specified permissions.
  This includes **user-based, role-based and group-based permissions**, which may come from different sources.

    ```php
    User::byAccessToAll(string ...$permissions): array
    ```

* Returns all active users that have **at least one** of the specified permissions.
  This includes **user-based, role-based and group-based permissions**.

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

* Creates a user object from a raw `z_user` row, which must contain the `id` key.

    ```php
    $user = new User(array $data);
    ```

---

### Creating a User

Users can be created through the static `add` method.

* Creates a new user.

    ```php
    User::add(?string $email, ?string $password, ?DateTime $verified = null);
    ```

If no verification date is provided, the user is created as **unverified**.

Throws an `InvalidArgumentException` if the email is not a valid address or already belongs to another user. Passing `null` creates a user without an email. A password outside 3 to 1024 bytes throws `InvalidArgumentException` only after the user row is inserted, so that user stays without a password.

The `$password` parameter is optional. When `null` is passed, the user is created without a password - useful for invite or SSO flows where a credential is set later via `updatePassword()`.

---

### Updating User Data

User attributes can be updated directly on the instance.

* Updates the user’s email address. Throws an `InvalidArgumentException` if the address is not a valid email or already belongs to another user. Passing `null` clears it. It updates only active rows and neither resets `verified` nor ends sessions.

    ```php
    $user->updateEmail(?string $email);
    ```

* Updates the user’s [password](password-handling.md).

    ```php
    $user->updatePassword(string $password);
    ```

* Assigns the user to an organization (`z_user.organizationId`), or unsets it when `null` is passed.
  If the previous and/or the new organization has a linked permission group (see `Organization::getGroup()`), the user's group memberships are kept in sync: the previous organization's group is removed (even when it was assigned manually, and skipped when that organization is already removed) and the new organization's group is added. The user's fields are cleared.

    ```php
    $user->updateOrganization(?Organization $organization);
    ```

* Clears all active login sessions for the user. Api keys are credentials of their
  own and are not touched.

    ```php
    $user->clearSessions();
    ```

---

### Removing a User

Removing a user performs a **soft delete**.

* Soft deletes the user (`active = 0`) and nulls the object, so any further call on it, `id()` included, throws `InvalidArgumentException("Instance no longer exists")`. Nothing else is touched, and the email stays taken (`z_user.email` is unique, inactive users included): the user's roles, sessions, api keys and reset codes stay as they are. Existing sessions stop identifying the user as logged in anyway, because the [login check](#bearer-authentication) requires `active = 1`; call [`$user->clearSessions()`](#updating-user-data) as well to end them.

    ```php
    $user->remove();
    ```

---

### Verification Handling

Verification status is time-aware and can be set or queried at arbitrary points in time.

* Marks the user as verified, overwriting an earlier date.
  Defaults to `NOW` if no date is provided.

    ```php
    $user->verify(?DateTime $date = null);
    ```

* Checks whether the user was verified at a specific time: `true` when `verified` is set and not later than `$at` (any `strtotime` string), so a future date counts as unverified now.

    ```php
    $user->isVerified(string $at = "NOW"): bool
    ```

---

### Two Factor

The account side of [two factor authentication](two-factor-authentication.md). The secret lives in `z_user.two_factor_secret`, and only a confirmed enrollment (`two_factor_confirmed_at`) turns two factor on.

* Starts an enrollment: stores a fresh secret that does not count yet and returns it as an `OTPHP\TOTP`. `getSecret()` and `getProvisioningUri()` are the two shapes an authenticator takes it in. The label is the user's email and the issuer the `pageName`. Starting again replaces the secret and turns an active two factor off until the new one is confirmed, which is why the profile page refuses to start while two factor is on.

    ```php
    $user->startTwoFactor(): TOTP
    ```

* Turns two factor on when the code matches the stored secret. Returns `false` for a wrong code and for an account that already has two factor.

    ```php
    $user->confirmTwoFactor(string $code): bool
    ```

* Checks a code against the stored secret, one 30 second step either side of the current one. Returns `false` while the account has no secret.

    ```php
    $user->verifyTwoFactorCode(string $code): bool
    ```

* Returns whether two factor is on, and since when.

    ```php
    $user->hasTwoFactor(): bool
    $user->twoFactorConfirmedAt(): ?string
    ```

* Turns two factor off and drops the secret.

    ```php
    $user->disableTwoFactor(): void
    ```

---

### Login Tries

Failed logins are counted per account to rate-limit password guessing.

* Returns `false` when the user has more failed tries than `maxLoginTriesPerTimespan` within the last `maxLoginTriesTimespan`. The event is logged as `ACCOUNT_LOGIN_RATE_LIMITED`. `maxLoginTriesTimespan` is a relative time string such as `3 minutes`. When it is unset, the window start becomes `1970-01-01`, so every recorded try of the account counts, however old. When `maxLoginTriesPerTimespan` is unset, it is `null` and the comparison only passes for zero tries, so the account is blocked as soon as a single try is recorded in the window.

    ```php
    $user->checkLoginTries(): bool
    ```

* Counts a failed login toward that limit.

    ```php
    $user->recordLoginTry(): void
    ```

```php
if(!$user->checkLoginTries()) {
    // blocked, notify the owner here if the application wants to
}

if(!$user->verifyPassword($password)) {
    $user->recordLoginTry();
}
```

The framework does not notify the owner of a blocked login, an application does that itself where it needs to.

---

### Role Assignment

Users can have multiple roles assigned or removed dynamically.

* Adds one or more roles to the user and clears the user's cached fields. Adding a role the user already has inserts a duplicate row.

    ```php
    $user->rolesAdd(Role ...$roles);
    ```

* Removes one or more roles from the user (the assignment is set inactive) and clears the user's cached fields.

    ```php
    $user->rolesRemove(Role ...$roles);
    ```

* `groupsAdd(Group ...$groups)` and `groupsRemove(Group ...$groups)` do the same for groups and reject plain `Role` objects.

---

### User-Based Permissions

Permissions can be assigned directly to users in addition to role-based permissions.

* Adds user-based permissions. Names are not deduplicated: an identical active row is inserted again.

    ```php
    $user->permissionsAdd(string ...$permissionNames);
    ```

* Removes user-based permissions.

    ```php
    $user->permissionsRemove(string ...$permissionNames);
    ```

---

### Permission Checks

Permission checks always resolve the **complete permission set**. The permission methods (`permissionsAdd`, `permissionsRemove`, `hasAccessAll`, `hasAccessAnyOf` and the static `buildPermissionVariants`) come from the `Permission` trait that `User`, `Role` and `Group` share.

* Checks whether the user has **all** specified permissions, wildcards included.

    ```php
    $user->hasAccessAll(string ...$permissionNames): bool
    ```

    Without arguments it returns `true`, since no permission is missing.

* Checks whether the user has **at least one** of the specified permissions, wildcards included.

    ```php
    $user->hasAccessAnyOf(string ...$permissionNames): bool
    ```

    Without arguments it returns `false`, since no permission can match.

---

### Permission Retrieval and Refresh

* Returns only user-based permissions.

    ```php
    $user->getUserPermissions(): array
    ```

* Returns all effective permissions (user-based + role-based + group-based), cached on the instance until a permission, role or group is changed through these objects, after which the getters re-query on every call. The rows look like `["name" => "x.y"]`, while `Role::getPermissions()` returns plain strings. Only active role links, roles and permission rows count; `z_user.active` is not filtered.

    ```php
    $user->getPermissions(): array
    ```

* Reloads user-based permissions.

    ```php
    $user->refreshPermissions();
    ```

* Reloads all permissions including roles. Changes made outside these objects (admin forms, direct SQL) only show up after this or `refresh()`.

    ```php
    $user->refreshAllPermissions();
    ```

---

### User Data & Utility Methods

* Reloads the user data from the database and throws `RuntimeException("Object no longer exists")` when the row is gone or inactive. Call it after `updateEmail()`, `updatePassword()`, `verify()` and role or group changes, which clear the object's fields; reading a field like `email()` before throws `Field 'email' does not exist`.

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
    $user->id(): int
    ```

* Returns the user's [UUID](#uuids).

    ```php
    $user->uuid(): string
    ```

* Returns the user's active roles without groups, cached on the instance.

    ```php
    $user->getRoles(): array
    ```

* Returns the user's active groups, cached on the instance.

    ```php
    $user->getGroups(): array
    ```

* Returns the organization the user belongs to, or `null` if none is assigned.
  The result is cached on the instance - including the `null` case - until `clearFields()` or a write operation invalidates it.

    ```php
    $user->organization(): ?Organization
    ```

* Returns whether the user belongs to the given organization. A removed organization has no members.

    ```php
    $user->isMemberOf(Organization $organization): bool
    ```

* Returns the cached data of the user as an array. This is the full `z_user` row, including the `password` hash and the `salt` column (`null` for natively hashed passwords), plus the internal cache keys `permissions`, `user-permissions`, `roles`, `groups` and `organization`. Never output it unfiltered, for example in a response or a log; read single values with `getField()` instead.

    ```php
    $user->getAll(): array
    ```

* Throws an `InvalidArgumentException` ("Instance no longer exists") once the object was nulled by `remove()`; every other method on it throws the same.

    ```php
    $user->checkInstance(): void
    ```

---

## Role Object

The [`Role`](../api/classes/ZubZet-Framework-Authentication-Permission-Role.html) object represents a named collection of permissions that can be assigned to users. A `Group` is a `Role` restricted to `z_role.is_group = 1`: it grants permissions the same way, has all methods below (returning only groups, so `Role::byId()` never finds a group id), and is accepted wherever a `Role` is type-hinted. Role and group names share one unique column.

### Role Retrieval

* Returns all roles assigned to a user.

    ```php
    Role::byUser(User $user): array
    ```

* Returns the active role with that exact name or `null`.

    ```php
    Role::byName(string $name): ?Role
    ```

* Returns all roles that have **all** specified permissions, wildcards included. Without arguments it returns all active roles.

    ```php
    Role::byAccessToAll(string ...$permissions): array
    ```

* Returns all roles that have **at least one** of the specified permissions, wildcards included.

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

* Creates a new role and returns it.

    ```php
    Role::add(string $roleName): Role;
    ```

* Creates a role from a raw `z_role` row, which must contain the `id` key.

    ```php
    $role = new Role(array $data);
    ```

---

### Updating and Removing Roles

* Updates the role name. All fields are cleared, so call `$role->refresh()` before reading the object again.

    ```php
    $role->update(string $newName);
    ```

* Soft deletes the role. The instance is nulled, so any further call on it throws `InvalidArgumentException("Instance no longer exists")`. Its permissions and user assignments stay but stop counting, and its name stays reserved.

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

* Returns all active users assigned to the role, cached until `$role->refresh()`.

    ```php
    $role->getUsers(): array
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

* Removes permissions from the role (soft delete).

    ```php
    $role->permissionsRemove(string ...$permissionNames);
    ```

* Replaces this role's permissions with the permissions of another role. The current permissions are removed and the source role's active permissions are copied over.

    ```php
    $role->setPermissionsByRole(Role $role);
    ```

---

### Role Permission Checks

* Checks whether the role has **all** specified permissions, wildcards included.

    ```php
    $role->hasAccessAll(string ...$permissionNames): bool
    ```

    Without arguments it returns `true`, since no permission is missing.

* Checks whether the role has **at least one** of the specified permissions, wildcards included.

    ```php
    $role->hasAccessAnyOf(string ...$permissionNames): bool
    ```

    Without arguments it returns `false`, since no permission can match.

---

### Refreshing Role Data

* Reloads the role data from the database.

    ```php
    $role->refresh();
    ```

---

## Organization Object

The [`Organization`](../api/classes/ZubZet-Framework-Authentication-Organization.html) object represents a group of users. Users can belong to at most one organization. An organization has a name (not unique, may be `null`) and is soft-deletable (`active`) like the other authentication objects.

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
  When `$createGroup` is `true`, a permission `Group` named `$groupName`, by default `"{$name}_Group"`, is created and linked to the organization (`z_organization.groupId`, `NULL` without a group). The linked group is then available via `$organization->getGroup()` and is used by `User::updateOrganization()` to sync group membership.

    ```php
    Organization::add(?string $name, bool $createGroup = false, ?string $groupName = null): Organization
    ```

---

### Updating and Removing Organizations

* Updates the organization's name.

    ```php
    $organization->updateName(string $name);
    ```

* Soft deletes the organization (`active = 0`). Its active members are detached first (`organizationId` is set to `NULL` and the membership in the linked group is removed) and the linked group is deactivated, so `$user->organization()` returns `null` for them.

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

* Returns whether the given user belongs to this organization. The counterpart of `$user->isMemberOf()`.

    ```php
    $organization->hasMember(User $user): bool
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

* Returns a session by its token string, or `null` for an unknown or invalidated token; an expired session that was not yet invalidated is still returned. The row decides the class, so a token belonging to an api key returns an [`APIKey`](#api-keys). This is the entry point of the authentication and therefore resolves both kinds, whichever class it is called on.

    ```php
    Session::byToken(string $token): Session|APIKey|null
    ```

* Returns all active logins of a user, expired ones not yet invalidated included, or an empty array. Api keys are listed by `APIKey::byUser()` instead.

    ```php
    Session::byUser(User $user): array
    ```

* Returns a login by its ID, or `null` if the ID belongs to an api key.

    ```php
    Session::byId(int|string $id): ?Session
    ```

* Returns all active logins matching the given IDs. Like `all()` and the UUID finders, it skips invalidated rows.

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

* Returns all active logins.

    ```php
    Session::all(): array
    ```

---

### Creating a Session

* Creates a new login session for a user. An optional `$userExec` can be passed to create an impersonation session where `$user` is the target user and `$userExec` is the acting. If omitted, both are set to `$user`. No cookie is sent; use `$res->loginAs()` for that. The optional `$name` labels the session, which is what a "your active sessions" list shows, and `$reason` records why it was created.

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

* The lifetimes the [profile page](../z-admin/profile.md) offers for a new key next to *Never expires*, in days mapped to their label.

    ```php
    APIKey::LIFETIMES // [1 => "1 day", 3 => "3 days", ..., 365 => "1 year"]
    ```

---

### Bearer Authentication

A client that cannot keep a cookie, like a script or another service, sends its token in the
`Authorization` header instead. Both a login and an [api key](#api-keys) authenticate this way.

```
Authorization: Bearer <token>
```

The bearer token wins over the `z_login_token` cookie. If it is invalid or expired the request is
anonymous, it does not fall back to the cookie.

Only a header that carries exactly one token counts as a bearer token. A header of another scheme,
like `Basic`, an empty `Bearer `, a value with more than one token like `Bearer a b`, and two
`Authorization` headers (Apache merges them into one comma separated value) carry no token. The
request then uses the cookie, if there is one. `$req->bearerToken()` returns the token of the
header, or `null`.

A valid session does not log a deactivated user in. Whichever way the token arrived, a session or an
api key, the request is anonymous (`$user->isLoggedIn` is `false`) as soon as the user's `active` is
`0`, for example after [`$user->remove()`](#removing-a-user). The check runs on every request, so it
takes effect immediately without ending the session.

---

### Lifetime Management

* Extends the session's lifetime by the given number of seconds on top of the base timeout and any existing extension. This only changes server-side validity and needs an active row, so an invalidated session cannot be revived.

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
    `z_login_token` cookie to expire after `loginTimeoutSeconds` (defaults to
    7 days when unset) regardless, so a browser drops the cookie at that point
    even though the session itself lives on. The expiry is fixed when the cookie
    is set at login and is not re-issued when the session is extended, so
    `extend()` and `setExtensionTime()` keep the session alive on the server but
    not the cookie. Clients that send the token themselves are unaffected.

---

### Invalidation

* Immediately invalidates the session, preventing further use. The object is nulled, so any further call on it throws `InvalidArgumentException("Instance no longer exists")`.

    ```php
    $session->invalidate(): void
    ```

* Ends every active login of a user in one statement, the session that issues it included. Api keys are credentials of their own and are left alone, so revoke those separately by calling `invalidate()` on each of [`APIKey::byUser()`](#api-keys). This is the static counterpart to [`$user->clearSessions()`](#updating-user-data), which is also what [`$user->updatePassword()`](#updating-user-data) runs - changing a password ends every login of the account, its keys keep working.

    ```php
    Session::clearForUser(User $user): void
    ```

---

### Expiry Check

* Returns `true` if the session has expired. A session exempt from expiring never is; otherwise the expiry is the one [fixed on the session](#lifetime-management), or, when it carries none, `created` plus the configured `loginTimeoutSeconds` (defaults to 7 days) plus any `extended_seconds`. The maths use PHP's `strtotime()` and `time()` on the database `created` value, so PHP and database timezones must agree.

    ```php
    $session->isExpired(): bool
    ```

* Returns the point in time the session becomes unusable, or `null` when the session is exempt from expiring. This is the effective expiry, whether it was fixed through [`setExpiresAt()`](#lifetime-management) or computed from the lifetime, which is absolute from creation and not sliding.

    ```php
    $session->expiresAt(): ?string
    ```

---

### Two Factor Checks

A session remembers when it last passed a [two factor](two-factor-authentication.md) check, in `z_logintoken.last_two_factor`, and how many wrong codes it may still send, in `remaining_two_factor_tries`.

* Returns whether the session has to pass two factor again. `false` for an account without two factor, `true` while the session never passed a check or once the last one is older than `$freshnessSeconds` (defaults to `two_factor_freshness_seconds`, 900). A session whose user is gone always has to. This is what [`$req->requireFreshTwoFactor()`](two-factor-authentication.md#guarding-an-action) asks.

    ```php
    $session->requireRenew(?int $freshnessSeconds = null): bool
    ```

* Returns when the session last passed a check, or `null` while it never did, and how many wrong codes it may still send (`two_factor_tries` to begin with, 5 by default).

    ```php
    $session->lastTwoFactor(): ?string
    $session->remainingTwoFactorTries(): int
    ```

* Stamps a passed check on the session, and gives it its full budget of tries back.

    ```php
    $session->recordTwoFactor(): void
    $session->resetTwoFactorTries(): void
    ```

* Spends one try on a wrong code and returns how many are left. At zero the caller is expected to sign the session out, the framework's own endpoints do.

    ```php
    $session->spendTwoFactorTry(): int
    ```

* A login that verified the code itself stamps the new session right away.

    ```php
    $res->loginAs(int $userId, ?int $user_exec = null, ?string $name = null, ?string $reason = null, bool $recordTwoFactor = false)
    ```

---

### Session Data

* Returns the session's [UUID](#uuids). It is the only identifier of a session that is safe to hand out.

    ```php
    $session->uuid(): string
    ```

* Returns the session token string. Issued tokens are a `zub-` prefix plus 32 random bytes in hex, 68 characters in total. Tokens issued before this format keep working, so do not validate one by its length. Tokens are stored as is in `z_logintoken.token`, and sessions are never deleted, only set to `active = 0` (logout, expiry, `clearSessions()`).

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

* Returns when the session was last used, or `null` while it has not been used since it was created. Every authenticated request refreshes it, but at most once per `session_last_used_throttle_seconds` (defaults to 60), so the timestamp costs one write per throttle window rather than one per request - read it as "last seen around then", not to the second. Set the key to `0` to write on every request. A request from a new address is always written, because that is what updates `ipLast()`.

    ```php
    $session->lastUsed(): ?string
    ```

---

### Refreshing Session Data

* Reloads the session data from the database. The getters return the loaded data without reloading, so call it after a setter such as `setName()` or `extend()` to see the change.

    ```php
    $session->refresh(): void
    ```