# Login: Login as another user
When logging in, the client exchanges it's identifier and password for a authentication token and logs in using this token from that point on. `$res->loginAs` allows you to login a current client as another user by their ```z_user.`id` ```. 

The authentication token will track the exec user though. using this data, you'll always know who was logged in as who. You can also set an exec_user yourself, using the second argument of loginAs. If you don't want to build the tracking yourself, just use `Log / Statistics` within the z_admin panel. You are also able to login as another user using the very same panel.

Example:
```php
$res->loginAs(1, 2); //Logs the requesting session in as user 1 and marks the real user as 2.
```