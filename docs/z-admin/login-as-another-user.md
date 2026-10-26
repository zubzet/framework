# Login: Login as another user
When logging in, the client exchanges it's identifier and password for a authentication token and logs in using this token from that point on. [`$res->loginAs`](../api/classes/ZubZet-Framework-Message-Response.html#method_loginAs) allows you to login a current client as another user by their ```z_user.`id` ```. 

The authentication token will track the exec user though. using this data, you'll always know who was logged in as who. You can also set an exec_user yourself, using the second argument of loginAs (default: the logged in user itself). You are also able to login as another user using the very same panel.

Example:
```php
$res->loginAs(1, 2); //Logs the requesting session in as user 1 and marks the real user as 2.
```

`loginAs` is a plain session issuer and has no guards: it performs no permission check (the caller decides who may use it), does not invalidate the user's other sessions (concurrent sessions are allowed) and does not change `user()` of the current request. The new login applies from the next request on, when the client sends the cookie. `$userId` is typed `int`, so a non-numeric string (for example from a URL) throws a `TypeError`. It logs `USER_LOGGED_IN`, or `USER_LOGGED_IN_ANOTHER` when `$user_exec` differs from `$userId`.

`$res->logout()` invalidates only the current session and logs `USER_LOGGED_OUT`. During a login as another user it then calls `loginAs()` for the exec user, which issues a fresh token, otherwise it deletes the cookie, and it always ends with a redirect to the root, also for a client that is not logged in.

## Naming a session and recording why it exists

Every session records the request that created it on its own: `device` holds the user agent and `ip_creation` the address. The two optional arguments are what you choose yourself - a `$name` for the label a "your active sessions" list shows, and a `$reason` for why the session exists, which is what makes an impersonation traceable afterwards:

```php
$res->loginAs(1, 2, "Support session", "Ticket #4711"); //Names the session and records why it was opened
```

Both values are read back through [`Session::byUser()`](../core-features/access-control.md#session-object).

## The session cookie

`loginAs` sends the token to the client as the `z_login_token` cookie, set through `setFrameworkCookie` (a wrapper around `setCookie`) with these attributes:

| Attribute | Value |
|---|---|
| `Expires` | `time() + loginTimeoutSeconds` (7 days when the setting is unset) |
| `Path` | `/` |
| `Domain` | `.<domain>` when `login_scope_allow_subdomains` is the string `true`, otherwise none (host-only). A second `z_login_token` delete header (on login and logout) is then sent for the domain in `login_scope_allow_subdomains_delete_domainscope_name` (to clean up cookies from before the switch) whenever that key exists, even empty, and an empty value deletes the host-only cookie just set |
| `Secure` | set when the `host` setting starts with `https://` |
| `HttpOnly` | never set, because Z.js and the session watcher read the cookie in JavaScript |
| `SameSite` | `Lax` |

## In the admin panel

The panel passes the current exec user on, so nested impersonation keeps the real identity, which `checkSuperPermission()` checks.

The "Login as" button on the user edit page asks for a reason before it sends you
on. Leaving it empty or cancelling still logs you in - the session then records
`z-admin impersonation` instead. Whatever is typed is cut to the 255 characters
the column takes, so a long explanation shortens rather than failing.