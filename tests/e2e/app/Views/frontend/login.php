<?php return [ 'body' => function($opt) { ?>
    <span data-test="userid"><?= $opt["userId"] ?></span>

    <div id="login-error-label" data-test="login-error"></div>

    <input id="input-email" type="email" data-test="email">

    <input id="input-password" type="password" data-test="password">

    <button id="button-login" data-test="submit">Submit</button>

    <script>
        document.getElementById("button-login").addEventListener("click", () => {
            Z.Presets.Login("input-email", "input-password", "login-error-label");
        });
    </script>
<?php }]; ?>