# Organization Management

Members of an [organization](../core-features/access-control.md#organization-object) can manage it themselves in the Z-Admin panel at `/z/organization`. The page has up to four sections, each behind its own permission:

| Section | Permission | Function |
| ------- | ---------- | -------- |
| Name | `z.organization.rename` | Rename the organization |
| Invite a member | `z.organization.invite` | Create an invitation link |
| Members | `z.organization.roles` | Give members roles and take them away again |
| Open invitations | `z.organization.invite` | List and revoke invitations that are not accepted yet |

A user who belongs to no organization sees a notice instead. The sidebar entry *Organization* (under *Other*) and the dashboard card show up with `z.organization.invite`. The other permissions reach the page through its direct link.

---

## Invitations

An invitation brings an existing account into the organization. No mail is sent: the page hands the inviting user a link to pass on.

- Only an active account that belongs to no organization can be invited. Every other address gets the same message, so the form does not reveal which case applies.
- An address holds one open invitation per organization at a time.
- An invitation is valid for **7 days**. Expired invitations are no longer listed and no longer block a new one.
- Revoking an invitation makes its link stop working right away.

The link leads to `/z/organization/invitation/{token}`. The invited person has to be logged in with the invited address. The page shows the organization, the address and when the invitation was created, and accepting it makes the account a member. The account also joins the organization's [linked group](../core-features/access-control.md#organization-data-access), like on any `$user->updateOrganization()`.

Unknown, expired and revoked links answer with a 404, and so does a link opened by another account or by a user who already belongs to an organization.

---

## Releasing Roles to Organizations

An organization can only hand out roles that are released to organizations. A role is released with the checkbox *Organizations may assign this role to their members* on its page under *Roles*, stored in `z_role.is_org_assignable`. The flag works for groups as well. The groups page cannot edit them yet, so a group is released in the database.

In the *Members* section every member gets a multi select of the released roles. Saving replaces the released roles the member holds with the selection. Roles that are not released stay untouched.

!!! warning
    A released role hands its permissions to whoever an organization chooses. Only release roles meant for that, and keep the organization's own management permissions out of them unless its members may pass them on.

---

## Components

The page is built from Blade components in the `zubzet::` namespace, so an application can place them on its own pages. Each one checks its permission and renders nothing without it. They post to the `z/organization/*` endpoints of the Z-Admin panel.

| Component | Props | Permission |
| --------- | ----- | ---------- |
| `<x-zubzet::organization.rename :name="$name"/>` | `name`: the current name | `z.organization.rename` |
| `<x-zubzet::organization.invite/>` | - | `z.organization.invite` |
| `<x-zubzet::organization.invites :invites="$invites"/>` | `invites`: invitation rows with an `expires_at` timestamp | `z.organization.invite` |
| `<x-zubzet::organization.members :members="$members" :food="$roleFood"/>` | `members`: `id`, `email` and the held `roles` ids per member; `food`: the released roles as `value`/`text` pairs | `z.organization.roles` |
| `<x-zubzet::organization.accept :token="$token"/>` | `token`: the invitation token | logged in |

The error messages come from `Z.Lang` in `Z.js`. Like its other texts, they can be overwritten in the layout after the layout essentials are embedded:

| Key | Default |
| --- | ------- |
| `error_user_unavailable` | This user cannot be invited right now. |
| `error_already_invited` | This address already has an open invitation. |
| `error_invalid_token` | This invitation is invalid or has expired. |
| `error_already_in_organization` | You are already a member of an organization. |
