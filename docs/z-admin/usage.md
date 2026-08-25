# What can it do?
The Z-Admin panel is a management panel you can open to edit/create users or roles/permissions.

# Using the Z-Admin panel
The Z-Admin panel is a control panel all projects using the framework have. It is accessible with the z controller. For example, an URL like this: `localhost/project/z` or `abcde.de/z` or `{yourdomain.tld}/{yourwebsite}/z`. Only logged in accounts with the correct [permissions](../core-features/permission-system.md) are able to see this section.

## Categories
It has following categories:

| Category | Function |
| -------- | -------- |
| Instance | Simple place to change instance settings|
| Log / Statistics | View [logs](../core-features/logging.md) and statistics
| Framework Update | Start [updates for the framework](../setup/how-to-update.md) |
| Edit User | Form to edit users
| Add User | Form to add users
| Roles | User permission managment |
| Organizations | List of organizations plus a detail page to rename or delete one |
| Add Organization | Form to create an organization, optionally with a permission group |


## Permissions
To be able to use all functions, the following permissions are needed:

- admin.panel
- admin.user.list
- admin.user.add
- admin.user.edit
- admin.roles.list
- admin.roles.create
- admin.roles.edit
- admin.roles.delete
- admin.organizations.list
- admin.organizations.create
- admin.organizations.edit
- admin.organizations.delete
- admin.log
- [admin.su](login-as-another-user.md)

## Assigning roles
In order to assign any roles, you must go to Edit Users and select the user you want to give a role to. Hit the ‘+’ under the title ‘Roles’ and select the role the user should get.
Be advised, each role gives special permissions, some give the user special powers, therefore please see the list above of what roles have which permissions.

After you set a role, you can either add more roles or save the user by clicking ‘submit’ at the bottom of the page. The user should now be able to use their role.

## Organizations
Add Organization creates an [organization](../core-features/access-control.md). Ticking **Create a permission group** also creates a [group](../core-features/permission-system.md) named after the organization (`{name}_Group`) and links it to the organization. Organization names may repeat, group names may not, so the checkbox reports an error when a group of that name already exists.

Organizations lists every active organization; opening one shows a form to rename it, the linked group, the users assigned to it, and a button to delete it. Deleting is a soft delete, the organization is set to inactive.

## Assigning organizations
Both Add User and Edit User carry an Organization select. Picking an entry assigns the user to that organization, the empty entry (`---`) unassigns them. The assignment goes through `User::updateOrganization()`, so the group of the previous organization is removed from the user and the group of the new one is added.
