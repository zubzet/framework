# Login: Login as another user
When logging in, the client exchanges it's identifier and password for a authentication token and logs in using this token from that point on. [`$res->loginAs`](../api/classes/ZubZet-Framework-Message-Response.html#method_loginAs) allows you to login a current client as another user by their ```z_user.`id` ```. 

The authentication token will track the exec user though. using this data, you'll always know who was logged in as who. You can also set an exec_user yourself, using the second argument of loginAs. If you don't want to build the tracking yourself, just use `Log / Statistics` within the [z_admin panel](usage.md). You are also able to login as another user using the very same panel.

Example:
```php
$res->loginAs(1, 2); //Logs the requesting session in as user 1 and marks the real user as 2.
```

## Naming a session and recording why it exists

Every session records the request that created it on its own: `device` holds the user agent and `ip_creation` the address. The two optional arguments are what you choose yourself - a `$name` for the label a "your active sessions" list shows, and a `$reason` for why the session exists, which is what makes an impersonation traceable afterwards:

```php
$res->loginAs(1, 2, "Support session", "Ticket #4711"); //Names the session and records why it was opened
```

Both values are read back through [`Session::byUser()`](../core-features/access-control.md#session-object).

## In the admin panel

The "Login as" button on the user edit page asks for a reason before it sends you
on. Leaving it empty or cancelling still logs you in - the session then records
`z-admin impersonation` instead. Whatever is typed is cut to the 255 characters
the column takes, so a long explanation shortens rather than failing.