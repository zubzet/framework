@php
    use ZubZet\Framework\Authentication\Organization;
@endphp

@auth
    @php
        // Null as well when the organization of the user no longer exists
        $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);
    @endphp

    @if(user()->checkPermission("z.organization.rename") && !is_null($organization))
        <div id="z-organization-rename-form" {{ $attributes }}></div>

        <script>
            $(() => {
                var form = Z.Forms.create({
                    dom: "z-organization-rename-form",
                    name: "z-organization-rename",
                    customEndpoint: Z.Request.rootPath + "_zubzet/organization/rename",
                    saveHook: () => $(".z-organization-name").text(nameField.value),
                });

                var nameField = form.createField({
                    name: "name",
                    type: "text",
                    text: <?= json_encode(__("organization.rename.name")) ?>,
                    required: true,
                    value: <?= json_encode($organization->name() ?? "", JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                });

                $(form.buttonSubmit).html('<i class="fa fa-fw fa-save"></i> ' + <?= json_encode(e(__("organization.rename.save"))) ?>);
            });
        </script>
    @endif
@endauth
