# Z Preset: Login and Register
The default JavaScript API of this framework (which is included in all views using a properly setup layout) contains presets for some core functionality of a web page.

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

To trigger the login action there is the function `Z.Presets.Login`. It takes the ids of the DOM elements as inputs.

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
Here the important method is `Z.Presets.Signup`. It works similar to login.