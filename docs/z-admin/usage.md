# What can it do?
The Z-Admin panel is a management panel you can open to edit/create users or roles/permissions.

# Using the Z-Admin panel
The Z-Admin panel is a control panel all projects using the framework have. It is accessible with the z controller. For example, an URL like this: `localhost/project/z` or `abcde.de/z` or `{yourdomain.tld}/{yourwebsite}/z`. Only logged in accounts with the correct permissions are able to see this section.

## Categories
It has following categories:

| Category | Function |
| -------- | -------- |
| Instance | Simple place to change instance settings|
| Log / Statistics | View logs and statistics
| Framework Update | Start updates for the framework |
| Edit User | Form to edit users
| Add User | Form to add users
| Roles | User permission managment |


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
- admin.log
- admin.su

## Assigning roles
In order to assign any roles, you must go to Edit Users and select the user you want to give a role to. Hit the ‘+’ under the title ‘Roles’ and select the role the user should get.
Be advised, each role gives special permissions, some give the user special powers, therefore please see the list above of what roles have which permissions.

After you set a role, you can either add more roles or save the user by clicking ‘submit’ at the bottom of the page. The user should now be able to use their role.
