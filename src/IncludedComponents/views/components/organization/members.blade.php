@php
    use ZubZet\Framework\Authentication\Organization;
@endphp

@auth
    @php
        // Null as well when the organization of the user no longer exists
        $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);
    @endphp

    @if(user()->checkPermission("z.organization.roles") && !is_null($organization))
        @php
            $members = model("z_organization")->getMembers($organization, showInactive: true);
        @endphp

        <ul {{ $attributes->class("list-group z-organization-members") }}>
            @forelse($members as $member)
                <li class="list-group-item" data-test="organization-member-{{ $member['id'] }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span @class(["text-truncate", "mr-3", "text-muted" => $member["deleted"]])>{{ $member["email"] ?? __("organization.members.no_email") }}</span>
                        <div class="flex-shrink-0">
                            @if($member["deleted"])
                                <span class="badge badge-secondary" data-test="organization-member-deleted">{{ __("organization.members.deleted") }}</span>
                            @else
                                <button
                                    class="btn btn-sm btn-outline-secondary"
                                    data-toggle="collapse"
                                    data-target="#z-organization-member-{{ $member['id'] }}-roles"
                                    aria-expanded="false"
                                    data-test="btn-edit-organization-member-roles"
                                >
                                    <i class="fa fa-fw fa-user-tag"></i> {{ __("organization.members.roles") }}
                                </button>
                            @endif
                            @if(user()->checkPermission("z.organization.remove"))
                                <button
                                    class="btn btn-sm btn-outline-danger z-organization-member-action"
                                    data-id="{{ $member['id'] }}"
                                    data-action="remove"
                                    data-confirm="{{ __("organization.members.remove_confirm") }}"
                                    data-test="btn-remove-organization-member"
                                >
                                    <i class="fa fa-fw fa-user-minus"></i> {{ __("organization.members.remove") }}
                                </button>
                            @endif
                            @unless($member["deleted"])
                                @if(user()->checkPermission("z.organization.delete"))
                                    <button
                                        class="btn btn-sm btn-outline-danger z-organization-member-action"
                                        data-id="{{ $member['id'] }}"
                                        data-action="delete"
                                        data-confirm="{{ __("organization.members.delete_confirm") }}"
                                        data-test="btn-delete-organization-member"
                                    >
                                        <i class="fa fa-fw fa-user-slash"></i> {{ __("organization.members.delete") }}
                                    </button>
                                @endif
                            @endunless
                        </div>
                    </div>
                    @unless($member["deleted"])
                        <div class="collapse" id="z-organization-member-{{ $member['id'] }}-roles">
                            <div class="pt-3" id="z-organization-member-{{ $member['id'] }}-form"></div>
                        </div>
                    @endunless
                </li>
            @empty
                <li class="list-group-item text-muted" data-test="organization-members-empty">{{ __("organization.members.empty") }}</li>
            @endforelse
        </ul>

        <script>
            $(() => {
                // Role names and addresses come from users, they must not close the script tag
                var food = <?= json_encode(model("z_organization")->getAssignableRoles(), JSON_HEX_TAG | JSON_HEX_AMP) ?>.map((role) => ({value: role.id, text: role.name}));

                <?= json_encode($members, JSON_HEX_TAG | JSON_HEX_AMP) ?>.filter((member) => !member.deleted).forEach((member) => {
                    var form = Z.Forms.create({
                        dom: "z-organization-member-" + member.id + "-form",
                        name: "z-organization-member-" + member.id,
                        customEndpoint: Z.Request.rootPath + "_zubzet/organization/members/" + member.id + "/roles",
                    });

                    form.createField({
                        name: "roles",
                        type: "multi-select",
                        text: <?= json_encode(__("organization.members.roles")) ?>,
                        placeholder: <?= json_encode(__("organization.members.roles_placeholder")) ?>,
                        food: food,
                        value: member.roles,
                    });

                    $(form.buttonSubmit).html('<i class="fa fa-fw fa-save"></i> ' + <?= json_encode(e(__("organization.members.save"))) ?>);
                });

                $(document).on("click", ".z-organization-member-action", function() {
                    var button = $(this);
                    if(!confirm(button.data("confirm"))) return;

                    button.prop("disabled", true);

                    Z.Request.root("_zubzet/organization/members/" + button.data("id") + "/" + button.data("action"), null, {}, (res) => {
                        if("success" == res.result) return location.reload();
                        button.prop("disabled", false);
                    });
                });
            });
        </script>
    @endif
@endauth
