# What can it do?
The Z-Admin panel is a management panel you can open to edit/create users or roles/permissions.

# Using the Z-Admin panel
The Z-Admin panel is a control panel all projects using the framework have. It is accessible with the z controller. For example, an URL like this: `localhost/project/z` or `abcde.de/z` or `{yourdomain.tld}/{yourwebsite}/z`. Only logged in accounts with the correct [permissions](../core-features/permission-system.md) are able to see this section; an anonymous visitor gets the login page instead.

## Categories
It has following categories:

| Category | Function |
| -------- | -------- |
| Database | Read-only [table browser](#database-browser) |
| Maintenance | Shows the state of the [maintenance mode](../core-features/maintenance.md) and sets the bypass cookie |
| Groups | List of permission groups |
| Edit User | Form to edit a user's email, roles, user permissions and organization; there is no password change
| Add User | Form to add users; the email must be unique and valid (empty is stored as NULL), the password is required client-side only. The user is verified at once, gets no role and no mail; a password outside 3 to 1024 bytes is rejected only after the user was inserted, so that email stays taken
| Roles | User permission managment |
| Organization | [Organization management](organization-management.md) by its own members |
| Profile | [Own password, two factor, sessions and api keys](profile.md) |
| Organizations | List of organizations plus a detail page to rename or delete one |
| Add Organization | Form to create an organization, optionally with a permission group |


The dashboard cards are fixed and there is no widget API; to change the panel, override its views (`administration/*`, `database/*`, `layout/z_admin_layout`) with a [view](../core-features/views.md) of the same name in your project (`administration/groups` gets the option `groups`, `database/tables` gets `status` with `tables` and `approxRows`). `layout/z_admin_layout` is the z controller's default layout and links `{root}assets/img/favicon.png`, which your app has to supply.

## Permissions
To be able to use all functions, the following permissions are needed:

- admin.panel (the dashboard at `/z` only)
- admin.user.list (the user list at `/z/edit_user`)
- admin.user.add (the Add User form and creating the user)
- admin.user.edit (opening and saving a single user)
- admin.groups.list (the groups list)
- admin.roles.list (the roles list; checked first on every `/z/roles` request, including create and delete)
- admin.roles.create (the `create` action of `/z/roles`; the new role has an empty name and role names are unique, so rename it before creating the next)
- admin.roles.edit (opening and saving a role; also required for delete)
- admin.roles.delete (the `delete` action on a role)
- admin.organizations.list
- admin.organizations.create
- admin.organizations.edit (opening and renaming an organization; also required for delete)
- admin.organizations.delete (the `delete` action on an organization)
- admin.maintenance
- [admin.database](#database-browser)
- [admin.su](login-as-another-user.md) (`/z/login_as/{userId}`, also used by the *Login as* button of the edit form)

`admin.panel` guards the dashboard at `/z` and nothing else. Every other action checks its own permission from the list above, so a user without `admin.panel` still reaches `/z/roles`, `/z/edit_user` and the rest by their direct URL, as long as they hold the permission of that action. The checks run against the effective user, so while [logged in as another user](login-as-another-user.md) that user needs the permission itself. The sidebar and the dashboard cards (icon only, in the sections Application, Users and Roles, and Other) only show the entries the user may open, except that Edit User is shown for `admin.user.edit` although its list needs `admin.user.list`.

Members manage their own organization with:

- [z.organization.manage](organization-management.md): the sidebar entry and dashboard card
- [z.organization.invite](organization-management.md#invitations)
- [z.organization.roles](organization-management.md#releasing-roles-to-organizations)
- [z.organization.rename](organization-management.md)

Profile is the exception: it only ever shows the requesting account, so every login reaches it without a permission.

The framework's own migrations create no admin role, user or permission. A project created with the install script ships a seed (`app/Database/seed/accounts/0_permissions.sql`) that adds the roles `Admin` (permission `*.*`) and `User` (permission `dashboard`), but no user account. Create a user, assign it the `Admin` role under Edit Users or in `z_user_role`, and for projects without that seed insert the grants into `z_role_permission` or `z_user_permission` yourself.

## Assigning roles
In order to assign any roles, you must go to Edit Users and select the user you want to give a role to. Hit the ‘+’ under the title ‘Roles’ and select the role the user should get.
The list offers every active role, but a group only while the user already holds it. Groups follow the user's [organization](../core-features/access-control.md#organization-object), so they are not handed out here.
Be advised, each role gives special permissions, some give the user special powers, therefore please see the list above of what roles have which permissions.

After you set a role, you can either add more roles or save the user by clicking ‘submit’ at the bottom of the page. The user should now be able to use their role.

## Two factor
An account with [two factor](../core-features/two-factor-authentication.md) shows *Disable two factor* on its page under Edit Users. It turns two factor off after a confirmation, so an owner who lost their authenticator can sign in with the password again and set it up anew. It needs `admin.user.edit`.
## Organizations
Add Organization creates an [organization](../core-features/access-control.md). Ticking **Create a permission group** also creates a [group](../core-features/permission-system.md) named after the organization (`{name}_Group`) and links it to the organization. Organization names may repeat, group names may not, so the checkbox reports an error when a group of that name already exists.

Organizations lists every active organization; opening one shows a form to rename it, the linked group, the users assigned to it, and a button to delete it. Renaming also renames the linked group when it is named `{organization name}_Group`, as created by the checkbox above, and fails with a uniqueness error when a group of the new name already exists. Deleting is a soft delete, the organization is set to inactive, its members are detached and the linked group is deactivated. The Delete button requires `admin.organizations.delete`.

## Assigning organizations
Both Add User and Edit User carry an Organization select. Picking an entry assigns the user to that organization, the empty entry (`---`) unassigns them. The assignment goes through `User::updateOrganization()`, so the group of the previous organization is removed from the user and the group of the new one is added. An Edit User request without the `organization` field leaves the organization unchanged.

## Database browser
`/z/database` lists every table of the database with approximate row counts, `/z/database/{table}` shows its rows 20 per page (`/page/{n}`, five page numbers around the current one, a page beyond the last shows no rows), newest first by the last primary key column (the first column without one), and `/z/database/{table}/csv` (the Export button) exports up to 10000 rows. The CSV export does not neutralise spreadsheet formulas. It is a read-only viewer and exporter (no insert, edit, delete, filter or search) and needs the permission `admin.database`.

Only the columns named exactly `password`, `pw`, `salt` or `hash` are shown as `********`, in the table view and in the CSV export. Every other column is shown in clear text, including `z_logintoken.token`, `z_email_verify.token` and `z_password_reset.refId`. Anyone holding `admin.database` can read these, and with them take over sessions or password resets, so grant it only to accounts you would trust with the database itself.
