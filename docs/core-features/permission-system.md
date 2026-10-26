# Using the permission system


## Basic explanation
A user can have multiple [roles](access-control.md), and each role can have multiple permissions. These can be managed in the [admin panel](../z-admin/usage.md).

## Roles
Roles are the things that holds permissions. A role can be created with the Z-Admin panel (`{root}/z`).
For a user to have access to the permissions of the role it needs to have the role assigned.
The assignment can also be done with the Z-Admin panel.

## Structure of a permission
Permissions are structured as multiple keywords connected by periods. For example: `admin.user.create`. This is descriptive as it can be seen that this permission is for admins with access to the user management and creating users. This style of writing permissions allows also the usage of wildcards. To `admin.user.create` someone with these permissions will have access to it: `*.*`, `admin.*`, `admin.user.*` and `admin.user.create`. `*.*` is a wildcard for all permissions, including single-word ones such as `support`. **Use it on you own risk**. A trailing `.*` also covers the prefix itself and everything below it (`a.b.*` satisfies `a.b` and `a.b.c`, not `a.x`). Wildcards only count on the granted side: a `*` inside a name or a lone `*` is plain text. The checks in PHP compare names case-sensitively, `byAccessToAll` and `byAccessToAnyOf` follow the database collation. Names are free text up to 255 characters.

## Blocking forbidden pages
The Request object has a method called [`checkPermission`](../api/classes/ZubZet-Framework-Message-Request.html#method_checkPermission).
By calling it, anonymous users are sent to `login/index` and logged in users without the permission given as the parameter to the 403 page, and the current request is cancled with `exit`. `checkSuperPermission($permission)` is the same check but also accepts the permission of the original user of a `loginAs` session, like `checkPermission($permission, false, true)`. With `true` as second parameter both return `false` instead. The reserved name `console` passes only on the CLI and otherwise ends in the 403 page, whatever the user holds.
So it is wise to call this at the very beginning of an action.

### Controller example:
```php
// Kicks the current request out of the action when it does not have "employee.view"
public function action_view(Request $req, Response $res) {
    $req->checkPermission("employee.view");

    return $res->render("employee/employee_view");
}
```
```php
// Returns a bool if the user has the permission
public function action_view(Request $req, Response $res) {
    if(!$req->checkPermission("employee.view", true)) { 
        return $res->render("forbidden");
    }

    return $res->render("employee/employee_view");
}
```

## Requiring an organization
[`requireOrganization`](../api/classes/ZubZet-Framework-Message-Request.html#method_requireOrganization) works like `checkPermission`, but ensures that the user belongs to an organization instead of checking a permission. A logged out user is sent to the login, and a logged in user without an organization is sent to the 403 page. A removed organization no longer counts.

```php
public function action_view(Request $req, Response $res) {
    $req->requireOrganization();

    return $res->render("employee/employee_view");
}
```
```php
// Returns a bool instead of showing the login or 403 page
if(!$req->requireOrganization(true)) {
    return $res->render("forbidden");
}
```

To check for a specific organization, use [`$organization->hasMember($user)`](access-control.md#organization-data-access) or [`$user->isMemberOf($organization)`](access-control.md#user-object).

## Checking permissions while rendering the page
To check if the requesting user has a permission, the user object in `$opt` can be used. It has a method called [`checkPermission`](../api/classes/ZubZet-Framework-Authentication-User.html#method_checkPermission) that returns a boolean. **Note** that this is **another** method than `checkPermission` from the request object. This one returns `false` for anonymous users, never redirects the user and can be used for example to determin if specific should be visible to the user on a page it generally has access to. `checkSuperPermission($permission)` also accepts the original user of a `loginAs` session, and `checkPermissionOf($permission, $userId)` checks any user id without a login check. Permissions are loaded once per user id per request and not refreshed when they change.

### View example:
```blade
@extends($layout)

@section("content")
    <!-- Returns a bool if the user has the permission -->
    <?php if($opt["user"]->checkPermission("employee.delete")) { ?>
        <button id="delete">Delete</button>
    <?php } ?>

    <!-- Remaining code -->
@endsection
```