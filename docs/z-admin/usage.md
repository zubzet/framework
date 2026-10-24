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
| Organization | [Organization management](organization-management.md) by its own members |
| Profile | [Own password, two factor, sessions and api keys](profile.md) |
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

Members manage their own organization with:

- [z.organization.manage](organization-management.md): the sidebar entry and dashboard card
- [z.organization.invite](organization-management.md#invitations)
- [z.organization.roles](organization-management.md#releasing-roles-to-organizations)
- [z.organization.rename](organization-management.md)

Profile is the exception: it only ever shows the requesting account, so every login reaches it without a permission.

## Assigning roles
In order to assign any roles, you must go to Edit Users and select the user you want to give a role to. Hit the ‘+’ under the title ‘Roles’ and select the role the user should get.
The list offers every role, but a group only while the user already holds it. Groups follow the user's [organization](../core-features/access-control.md#organization-object), so they are not handed out here.
Be advised, each role gives special permissions, some give the user special powers, therefore please see the list above of what roles have which permissions.

After you set a role, you can either add more roles or save the user by clicking ‘submit’ at the bottom of the page. The user should now be able to use their role.

## Two factor
An account with [two factor](../core-features/two-factor-authentication.md) shows *Disable two factor* on its page under Edit Users. It turns two factor off after a confirmation, so an owner who lost their authenticator can sign in with the password again and set it up anew. It needs `admin.user.edit`.
## Organizations
Add Organization creates an [organization](../core-features/access-control.md). Ticking **Create a permission group** also creates a [group](../core-features/permission-system.md) named after the organization (`{name}_Group`) and links it to the organization. Organization names may repeat, group names may not, so the checkbox reports an error when a group of that name already exists.

Organizations lists every active organization; opening one shows a form to rename it, the linked group, the users assigned to it, and a button to delete it. Renaming also renames the linked group when it is named `{organization name}_Group`, as created by the checkbox above, and fails with a uniqueness error when a group of the new name already exists. Deleting is a soft delete, the organization is set to inactive, its members are detached and the linked group is deactivated. The Delete button requires `admin.organizations.delete`.

## Assigning organizations
Both Add User and Edit User carry an Organization select. Picking an entry assigns the user to that organization, the empty entry (`---`) unassigns them. The assignment goes through `User::updateOrganization()`, so the group of the previous organization is removed from the user and the group of the new one is added. An Edit User request without the `organization` field leaves the organization unchanged.
