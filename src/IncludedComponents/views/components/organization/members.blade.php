@props(["members", "food"])

@auth
    @if(user()->checkPermission("z.organization.roles") && !is_null(user()->orgId))
        <ul {{ $attributes->class("list-group z-organization-members") }}>
            @forelse($members as $member)
                <li class="list-group-item" data-test="organization-member-{{ $member['id'] }}">
                    <div class="mb-2">{{ $member["email"] ?? "No email" }}</div>
                    <div id="z-organization-member-{{ $member['id'] }}-form"></div>
                </li>
            @empty
                <li class="list-group-item text-muted" data-test="organization-members-empty">No members.</li>
            @endforelse
        </ul>

        <script>
            $(() => {
                var food = <?= $food ?>;

                <?= json_encode($members) ?>.forEach((member) => {
                    var form = Z.Forms.create({
                        dom: "z-organization-member-" + member.id + "-form",
                        name: "z-organization-member-" + member.id,
                        customEndpoint: Z.Request.rootPath + "z/organization/roles/" + member.id,
                    });

                    form.createField({
                        name: "roles",
                        type: "multi-select",
                        text: "Roles",
                        placeholder: "Add a role...",
                        food: food,
                        value: member.roles,
                    });
                });
            });
        </script>
    @endif
@endauth
