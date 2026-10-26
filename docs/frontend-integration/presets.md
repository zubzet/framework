# Z Preset: Login and Register
The default JavaScript API of this framework, the bundled Z.js script (which is included in all [views](../core-features/views.md) using a properly setup [layout](../core-features/layouts.md)), contains presets for some core functionality of a web page.

For these two examples it is important that the default loginController is used.
## Creating a login
This is the basic layout for a login page:
```html
<div id="login-error-label"></div>

<label for="input-email">Email: </label>
<input id="input-email" type="email">

<label for="input-password">Password: </label>
<input id="input-password" type="password">

<button id="button-login">Log in</button>

<script>
    document.getElementById("button-login").addEventListener("click", () => {
        Z.Presets.Login("input-email", "input-password", "login-error-label");
    });
</script>
```
For this to work, three elements are needed. One error output label for reporting to the user if the login failed, and two inputs. The labels are technically not needed here as well as the button.

To trigger the login action there is the function `Z.Presets.Login`. It takes the ids of the DOM elements as inputs, posts `name` and `password` with `action=login` to `login`, reloads the page on success (or goes to `redirect`) and inserts any other server message into the error label as HTML. The bundled `login` answers an unknown email and a wrong password alike with `Username or password is wrong` (shown as `Z.Lang.error_login`; only a wrong password records a try), points an unverified account to `login/verify` before the password is checked, and rejects a locked account with `Too many login tries. Try again later.` before checking it, so even a correct password fails while locked; the preset shows `Z.Lang.error_too_many_login_tries` instead of that message. A success clears no tries and ends no other session. Its pages (`login`, `login_signup`, `login_forgotpassword`, `login_reset`, `login_verify`, `login_verify_wait`) are Blade views in `layout/min_layout`, replaced by a view of the same name in your project.

`Z.Presets.ForgotPassword(unameemailElementId, errorLabel, redirect)` posts `unameemail` to `login/forgot_password` and on any failure shows `Z.Lang.error_password_reset`, so the answer reveals whether the account exists. The presets show the element with id `loading` (hidden by default, styled by `_zubzet/asset-proxy/css/loadCircle.css`) while their request runs.

Optionally a fourth argument can be passed into the login function that gives the url to redirect to after a successfull login. This parameter should be absolute. Example:
```js
document.getElementById("button-login").addEventListener("click", () => {
    Z.Presets.Login("input-username", "input-password", "login-error-label", "<?php echo $opt["root"]; ?>");
});
```
## Creating a signup form
This is the basic layout for a singup page:
```html
<div id="login-error-label"></div>

<label for="input-email">Username: </label>
<input id="input-username" type="text">

<label for="input-password">Password: </label>
<input id="input-password" type="password">

<label for="input-password-confirm">Confirm Password: </label>
<input id="input-password-confirm" type="password">

<button id="button-signup">Sign up</button>

<script>
    document.getElementById("button-signup").addEventListener("click", () => {
        Z.Presets.Signup("input-username", "input-password", "input-password-confirm", "login-error-label", "<?php echo $opt["root"]; ?>");
    });
</script>
```
Here the important method is `Z.Presets.Signup`. It works similar to login (the message `This email is not allowed!` is shown as `Z.Lang.error_invalid_email`) and posts `email` and `password`, merged with the optional `additionalData` object (for example `{userRoleType: "Secondary"}`), with `action=signup` to `login/signup`. With `alertErrors` errors are also shown in an `alert()` (a password mismatch only there), and a hidden error label is not made visible.

The bundled `login/signup` action is open to anyone who can reach it and cannot be switched off with a setting. It has no CAPTCHA and no rate limit: every post with `action=signup`, an unused `email` and a `password` of 3 to 64 characters creates an account, assigns the role from `registerRoleId` (or `registerRoleIdSecondary` for the POST field `userRoleType=Secondary`; skipped when empty or not numeric) and sends a verification mail. Any validation failure answers `This email is not allowed!`, the new user is not logged in and the result of sending the mail is ignored. A verification link (`login/verify/<token>`) is valid for one day, opening it again re-stamps `verified`, and a post with `email` resends it and renders the same page whether or not the address exists. If the app should not allow public registration, or needs protection against automated signups, override the `login` controller in your own project or restrict the route yourself.
