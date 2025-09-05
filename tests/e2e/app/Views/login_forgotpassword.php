<?php return ["body" => function ($opt) { ?>
    <div id="reset-error-label" style="display: none;" data-test="error"></div>
    <input type="email" id="username" data-test="username">
    <a class="text-primary" href="<?= $opt["root"]; ?>">
        Zurück zum Login
    </a>
    <button id="btnReset" data-test="btn-login">save</button>

    <script>
        function reset() {
            Z.Presets.ForgotPassword("username", "reset-error-label")
        }

        $("#btnReset").click(reset);

        $("#username").keyup((e) => {
            if(e.keyCode == 13) reset();
        });
    </script>
<?php }]; ?>
