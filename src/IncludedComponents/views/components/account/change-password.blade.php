@auth
    <div id="z-change-password" {{ $attributes }} data-test="password-form"></div>

    <script>
        $(() => {
            var passwordForm = Z.Forms.create({
                dom: "z-change-password",
                name: "password",
                doReload: true,
                customEndpoint: Z.Request.rootPath + "_zubzet/profile/change-password",
            });

            passwordForm.createField({
                name: "password_current",
                type: "password",
                text: <?= json_encode(__("account.change_password.current")) ?>,
                required: true
            });

            passwordForm.createField({
                name: "password_new",
                type: "password",
                text: <?= json_encode(__("account.change_password.new")) ?>,
                required: true
            });

            passwordForm.createField({
                name: "password_repeat",
                type: "password",
                text: <?= json_encode(__("account.change_password.repeat")) ?>,
                required: true
            });
        });
    </script>
@endauth
