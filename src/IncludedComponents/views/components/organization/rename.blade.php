@props(["name"])

@auth
    @if(user()->checkPermission("z.organization.rename") && !is_null(user()->orgId))
        <div id="z-organization-rename-form" {{ $attributes }}></div>

        <script>
            $(() => {
                var form = Z.Forms.create({
                    dom: "z-organization-rename-form",
                    name: "z-organization-rename",
                    customEndpoint: Z.Request.rootPath + "z/organization/rename",
                    saveHook: () => $(".z-organization-name").text(nameField.value),
                });

                var nameField = form.createField({
                    name: "name",
                    type: "text",
                    text: "Name",
                    required: true,
                    value: <?= json_encode($name ?? "", JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                });

                $(form.buttonSubmit).html('<i class="fa fa-fw fa-save"></i> Save name');
            });
        </script>
    @endif
@endauth
