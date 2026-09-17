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
                text: "Current password",
                required: true
            });

            passwordForm.createField({
                name: "password_new",
                type: "password",
                text: "New password",
                required: true
            });

            passwordForm.createField({
                name: "password_repeat",
                type: "password",
                text: "Repeat new password",
                required: true
            });
        });
    </script>
@endauth
