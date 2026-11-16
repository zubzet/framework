# Organization Management

Members of an [organization](../core-features/access-control.md#organization-object) can manage it themselves in the Z-Admin panel at `/z/organization`. The page has up to four sections, each behind its own permission:

| Section | Permission | Function |
| ------- | ---------- | -------- |
| Name | `z.organization.rename` | Rename the organization |
| Invite a member | `z.organization.invite` | Invite a person by link or email |
| Members | `z.organization.roles` | Give members roles and take them away again |
| Open invitations | `z.organization.invite` | List and revoke invitations that are not accepted yet |

A user who belongs to no organization sees a notice instead. The sidebar entry *Organization* (under *Other*) and the dashboard card show up with `z.organization.manage`. Without it the page is still reached through its direct link.

---

## Invitations

An invitation brings a person without an account into the organization. The account is created when the invitation is accepted.

- The address must not belong to any account, a deactivated one included. The form answers that the user cannot be invited right now, without revealing the account.
- The inviter can pick one of the roles released to organizations. The new account gets it when the invitation is accepted, unless the role is no longer released by then.
- *Send email* is only offered with `z.organization.invite.email`, without it the inviter always gets the link. With it ticked, the invitation is mailed to the address in the language of the inviter and the inviter gets no link, so the mail alone proves the address. Without it, the page hands the inviter a link to pass on.
- An address holds one open invitation per organization at a time.
- An invitation is valid for **7 days**. Expired invitations are no longer listed and no longer block a new one.
- Revoking an invitation makes its link stop working right away.

The link leads to `/z/organization/invitation/{token}` and needs no login. The page shows the organization, the address and when the invitation was created, and asks for a password. Accepting creates the account with that address, already verified, and makes it a member. The account also joins the organization's [linked group](../core-features/access-control.md#organization-data-access), like on any `$user->updateOrganization()`. The new member is not logged in but sent to the login page.

Unknown, expired and revoked links answer with a 404, and so does a link whose address got an account in the meantime.

---

## Releasing Roles to Organizations

An organization can only hand out roles that are released to organizations. A role is released with the checkbox *Organizations may assign this role to their members* on its page under *Roles*, stored in `z_role.is_org_assignable`. The flag works for groups as well. The groups page cannot edit them yet, so a group is released in the database.

In the *Members* section every member gets a multi select of the released roles. Saving replaces the released roles the member holds with the selection. Roles that are not released stay untouched.

!!! warning
    A released role hands its permissions to whoever an organization chooses. Only release roles meant for that, and keep the organization's own management permissions out of them unless its members may pass them on.

---

## Components

The page is built from Blade components in the `zubzet::` namespace, so an application can place them on its own pages. Each one checks its permission, renders nothing without it and loads the data of the current user's organization itself. They post to the framework's `_zubzet/organization/*` endpoints.

| Component | Props | Permission |
| --------- | ----- | ---------- |
| `<x-zubzet::organization.rename/>` | - | `z.organization.rename` |
| `<x-zubzet::organization.invite/>` | - | `z.organization.invite` |
| `<x-zubzet::organization.invites/>` | - | `z.organization.invite` |
| `<x-zubzet::organization.members/>` | - | `z.organization.roles` |
| `<x-zubzet::organization.accept :token="$token"/>` | `token`: the invitation token | none |

The error messages come from `Z.Lang` in `Z.js`. Like its other texts, they can be overwritten in the layout after the layout essentials are embedded:

| Key | Default |
| --- | ------- |
| `error_user_unavailable` | This user cannot be invited right now. |
| `error_already_invited` | This address already has an open invitation. |
