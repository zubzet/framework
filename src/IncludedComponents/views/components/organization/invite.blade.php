@auth
    @if(user()->checkPermission("z.organization.invite") && !is_null(user()->orgId))
        <div {{ $attributes->class("z-organization-invite") }}>
            <div id="z-organization-invite-form"></div>

            <div class="alert alert-success mt-2 mb-0 d-none z-organization-invite-sent" data-test="organization-invite-sent">
                {{ __("organization.invite.sent") }}
            </div>

            <div class="input-group mt-2 d-none z-organization-invite-result" data-test="organization-invite-result">
                <input class="form-control z-organization-invite-link" data-test="organization-invite-link" readonly>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary z-organization-invite-copy" data-test="btn-copy-organization-invite">
                        <i class="fa fa-fw fa-copy"></i> {{ __("organization.invite.copy") }}
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
                    customEndpoint: Z.Request.rootPath + "_zubzet/organization/invites",
                    saveHook: (res) => {
                        // The link stays until the next invite, the field is free for it
                        emailField.value = "";
                        root.find(".z-organization-invite-sent, .z-organization-invite-result").addClass("d-none");

                        // A mailed invitation has no link for the inviter
                        if(!res.invite_link) return root.find(".z-organization-invite-sent").removeClass("d-none");

                        root.find(".z-organization-invite-link").val(res.invite_link);
                        root.find(".z-organization-invite-copy").html('<i class="fa fa-fw fa-copy"></i> ' + <?= json_encode(e(__("organization.invite.copy"))) ?>);
                        root.find(".z-organization-invite-result").removeClass("d-none");
                    },
                });

                var emailField = form.createField({
                    name: "email",
                    type: "email",
                    text: <?= json_encode(__("organization.invite.email")) ?>,
                    required: true,
                });

                form.createField({
                    name: "roleId",
                    type: "select",
                    text: <?= json_encode(__("organization.invite.role")) ?>,
                    // Role names come from users, they must not close the script tag
                    food: <?= json_encode(model("z_organization")->getAssignableRoles(), JSON_HEX_TAG | JSON_HEX_AMP) ?>.map((role) => ({value: role.id, text: role.name})),
                });

                @if(user()->checkPermission("z.organization.invite.email"))
                    form.createField({
                        name: "send_email",
                        type: "checkbox",
                        text: <?= json_encode(__("organization.invite.send_email")) ?>,
                    });
                @endif

                $(form.buttonSubmit).html('<i class="fa fa-fw fa-user-plus"></i> ' + <?= json_encode(e(__("organization.invite.create"))) ?>);

                root.on("click", ".z-organization-invite-copy", function() {
                    navigator.clipboard.writeText(root.find(".z-organization-invite-link").val());
                    $(this).html('<i class="fa fa-fw fa-check"></i> ' + <?= json_encode(e(__("organization.invite.copied"))) ?>);
                });
            });
        </script>
    @endif
@endauth
