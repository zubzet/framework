<?php return ["body" => function ($opt) { ?>

    <div id="login-error-label" data-test="error"></div>

    <input type="email" id="username" data-test="username">
    <input type="password" id="password" data-test="password">

    <button id="btnLogin" data-test="btn-login">
        Einloggen
    </button>
    <a class="text-primary" href="<?= $opt["root"]; ?>login/forgot-password">
        Passwort vergessen?
    </a>

    <script>
        function login() {
            Z.Presets.Login("username", "password", "login-error-label");
        }

        $("#btnLogin").click(() => {
            login();
        });

        $("#username, #password").keyup((e) => {
            if(e.keyCode == 13) login();
        });
    </script>
<?php }]; ?>
