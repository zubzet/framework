@auth
    @if(user()->checkPermission("z.organization.invite") && !is_null(user()->orgId))
        <div {{ $attributes->class("z-organization-invite") }}>
            <div id="z-organization-invite-form"></div>

            <div class="input-group mt-2 d-none z-organization-invite-result" data-test="organization-invite-result">
                <input class="form-control z-organization-invite-link" data-test="organization-invite-link" readonly>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary z-organization-invite-copy" data-test="btn-copy-organization-invite">
                        <i class="fa fa-fw fa-copy"></i> Copy
                    </button>
                </div>
            </div>
        </div>

        <script>
            $(() => {
                var root = $(".z-organization-invite");

                var form = Z.Forms.create({
                    dom: "z-organization-invite-form",
                    name: "z-organization-invite",
                    customEndpoint: Z.Request.rootPath + "z/organization/invite",
                    saveHook: (res) => {
                        // The link stays until the next invite, the field is free for it
                        emailField.value = "";
                        root.find(".z-organization-invite-link").val(res.invite_link);
                        root.find(".z-organization-invite-copy").html('<i class="fa fa-fw fa-copy"></i> Copy');
                        root.find(".z-organization-invite-result").removeClass("d-none");
                    },
                });

                var emailField = form.createField({
                    name: "email",
                    type: "email",
                    text: "Email",
                    required: true,
                });

                root.on("click", ".z-organization-invite-copy", function() {
                    navigator.clipboard.writeText(root.find(".z-organization-invite-link").val());
                    $(this).html('<i class="fa fa-fw fa-check"></i> Copied');
                });
            });
        </script>
    @endif
@endauth
