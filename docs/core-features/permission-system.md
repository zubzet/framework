# Using the permission system


## Basic explanation
A user can have multiple roles, and each role can have multiple permissions. These can be managed in the admin panel.

## Roles
Roles are the things that holds permissions. A role can be created with the Z-Admin panel (`{root}/z`).
For a user to have access to the permissions of the role it needs to have the role assigned.
The assignment can also be done with the Z-Admin panel.

## Structure of a permission
Permissions are structured as multiple keywords connected by periods. For example: `admin.user.create`. This is descriptive as it can be seen that this permission is for admins with access to the user management and creating users. This style of writing permissions allows also the usage of wildcards. To `admin.user.create` someone with these permissions will have access to it: `*.*`, `admin.*`, `amdin.user.*` and `admin.user.create`. `*.*` is a wildcard for all permissions. **Use it on you own risk**.

## Blocking forbidden pages
The Request object has a method called `checkPermission`.
By calling it, all users without the permission given as the parameter will be rerouted to the 403 page and the current request will be cancled.
So it is wise to call this at the very beginning of an action.

### Controller example:
```php
// Kicks the current request out of the action when it does not have "employee.view"
public function action_view(Request $req, Response $res) {
    $req->checkPermission("employee.view");

    return $res->render("employee/employee_view.php");
}
```
```php
// Returns a bool if the user has the permission
public function action_view(Request $req, Response $res) {
    if(!$req->checkPermission("employee.view", true)) { 
        return $res->render("forbidden.php");
    }

    return $res->render("employee/employee_view.php");
}
```
## Checking permissions while rendering the page
To check if the requesting user has a permission, the user object in `$opt` can be used. It has a method called `checkPermission` that returns a boolean. **Note** that this is **another** method than `checkPermission` from the request object. This one does not redirect the user and can be used for example to determin if specific should be visible to the user on a page it generally has access to.

### View example:
```php

<?php return ["body" => function ($opt) { ?>

    <!-- Returns a bool if the user has the permission -->
    <?php if($opt["user"]->checkPermission("employee.delete")) { ?>
        <button id="delete">Delete</button>
    <?php } ?>

    <!-- Remaining code -->
<?php }]; ?>
```