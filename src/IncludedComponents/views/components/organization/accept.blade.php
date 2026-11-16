@props(["token"])

<div {{ $attributes->class("z-organization-accept") }}>
    <div id="z-organization-accept-form" data-test="organization-accept-form"></div>
</div>

<script>
    $(() => {
        var form = Z.Forms.create({
            dom: "z-organization-accept-form",
            name: "z-organization-accept",
            customEndpoint: Z.Request.rootPath + "_zubzet/organization/invitations/" + <?= json_encode($token) ?> + "/accept",
            saveHook: () => {
                location.href = Z.Request.rootPath + "login";
            },
        });

        form.createField({
            name: "password",
            type: "password",
            text: <?= json_encode(__("account.change_password.new")) ?>,
            required: true,
        });

        form.createField({
            name: "password_repeat",
            type: "password",
            text: <?= json_encode(__("account.change_password.repeat")) ?>,
            required: true,
        });

        $(form.buttonSubmit).html('<i class="fa fa-fw fa-check text-white"></i> ' + <?= json_encode(e(__("organization.accept"))) ?>);
    });
</script>
