# Access Control

ZubZet now includes significantly enhanced **access control and permission capabilities**.
The system is built to provide a **flexible and extensible authorization workflow**, improved **developer ergonomics**, and full support for **user-based and role-based permissions**.

At its core, the access control system introduces two primary domain objects: **User** and **Role**.
Permissions can be assigned directly to users or indirectly through roles. All permission checks automatically resolve the **combined permission set**.

---

## User Object

The `User` object represents an application user and exposes a comprehensive API for retrieval, lifecycle management, and permission handling.

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
    User::add(?string $email, string $password, ?DateTime $verified = null);
    ```

If no verification date is provided, the user is created as **unverified**.

---

### Updating User Data

User attributes can be updated directly on the instance.

* Updates the user’s email address.

    ```php
    $user->updateEmail(?string $email);
    ```

* Updates the user’s password.

    ```php
    $user->updatePassword(string $password);
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

* Validates that the instance exists and is not null.

    ```php
    $user->checkInstance(): bool
    ```

---

## Role Object

The `Role` object represents a named collection of permissions that can be assigned to users.

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