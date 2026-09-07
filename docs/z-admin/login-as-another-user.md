# Login: Login as another user
When logging in, the client exchanges it's identifier and password for a authentication token and logs in using this token from that point on. [`$res->loginAs`](../api/classes/ZubZet-Framework-Message-Response.html#method_loginAs) allows you to login a current client as another user by their ```z_user.`id` ```. 

The authentication token will track the exec user though. using this data, you'll always know who was logged in as who. You can also set an exec_user yourself, using the second argument of loginAs. If you don't want to build the tracking yourself, just use `Log / Statistics` within the [z_admin panel](usage.md). You are also able to login as another user using the very same panel.

Example:
```php
$res->loginAs(1, 2); //Logs the requesting session in as user 1 and marks the real user as 2.
```

## Naming the session

Every session created this way is named after the user agent of the request that started it, cut to the 255 characters the column holds. A request without a user agent produces an unnamed session. The third argument overrides this, which is useful for impersonation, where the user agent describes the acting admin rather than the session itself:

```php
$res->loginAs(1, 2, "Support impersonation"); //Names the session instead of using the user agent
```

The name is what [`Session::byUser()`](../core-features/access-control.md#session-object) reports, so it is the label a "your active sessions" list shows.