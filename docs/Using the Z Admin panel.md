# Using the Z-Admin panel
The Z-Admin panel is a control panel all projects using the framework have. It is accessible with the z controller. For example, an URL like this: `{yourdomain.tld}/{yourwebsite}/z`. Only logged in accounts with the correct permissions are able to see this section.
## Categories
It has following categories:

| Category | Function | Read more |
| -------- | -------- | -------- |
| Instance | Simple place to change instance settings| [Wiki](https://git.zierhut-it.de/Zierhut-IT/z_framework/wiki/The-Booter-Settings) |
| Log / Statistics | View logs and statistics
| Framework Update | Start updates for the framework | [Wiki](https://git.zierhut-it.de/Zierhut-IT/z_framework/wiki/Updating-to-the-newest-version-of-the-framework)
| Edit User | Form to edit users
| Add User | Form to add users
| Roles | User permission managment | [Wiki](https://git.zierhut-it.de/Zierhut-IT/z_framework/wiki/Using-the-Permissions-System)
## Permissions
To be able to use all functions, the following permissions are needed:
*  admin.panel
*  admin.user.list
*  admin.user.add
*  admin.user.edit
*  admin.roles.list
*  admin.roles.screate
*  admin.roles.edit
*  admin.roles.delete
*  admin.log
*  admin.su
*  admin.danger.cfg
*  admin.danger.update

## Assigning roles
In order to assign any roles, you must go to Edit Users and select the user you want to give a role to. Hit the ‘+’ under the title ‘Roles’ and select the role the user should get. Be advised, each role gives special permissions, some give the user special powers, therefore please see the list below of what roles have which permissions.

After you set a role, you can either add more roles or save the user by clicking ‘submit’ at the bottom of the page. The user should now be able to use their role. 