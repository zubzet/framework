# Profile

Every logged in account has a profile page in the Z-Admin panel at `/z/profile`. It is linked as *Profile* in the sidebar (under *Other*) and on the dashboard. The page only ever shows the requesting account, so it needs no permission.

| Section | Function |
| ------- | -------- |
| Your data | Email, member since and the organization |
| Change password | Current, new and repeated password |
| Two factor | Set up and turn off [two factor authentication](../core-features/two-factor-authentication.md) |
| Sessions | Every login of the account, with rename and revoke, and *Clear all sessions* |
| API keys | Create, rename and revoke [api keys](../core-features/access-control.md#api-keys) |

---

## Password

A new password ends every login of the account, the current browser included, so it has to sign in again. Api keys keep working, they are credentials of their own.

## Two Factor

*Set up two factor* shows a QR code for the authenticator and the key to type in by hand. Two factor stays off until a code from the authenticator confirms that the secret arrived. Turning it off again asks for a current code. After 5 wrong codes the session is signed out.

An owner who lost their authenticator cannot turn it off themselves. An admin with `admin.user.edit` can, with *Disable two factor* on the account's page under *Edit User*.

## Sessions

Every login is listed with its name, the device, where it started and was last seen, and when it expires. The current one is marked *This browser*. A session can be renamed and revoked one by one, or all at once with *Clear all sessions*, which ends the current login as well. The last use is refreshed at most once per `session_last_used_throttle_seconds` (defaults to 60), so it reads as "around then".

## API Keys

A key is created with a name and a lifetime between 1 day and 1 year, or without an expiry. Its token is shown once, right after creating it, and cannot be read again. Keys are listed with the same details as sessions and can be renamed and revoked the same way.

---

The page is built from the [account components](../core-features/views.md#account-components), so the same pieces can be placed on a page of an application.
