@props(["invites"])

@auth
    @if(user()->checkPermission("z.organization.invite") && !is_null(user()->orgId))
        <ul {{ $attributes->class("list-group z-organization-invites") }}>
            @forelse($invites as $invite)
                <li class="list-group-item" data-test="organization-invite-{{ $invite['id'] }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="mr-3">
                            <div>{{ $invite["email"] }}</div>
                            <small class="text-muted d-block">
                                {{ __("organization.invites.validity", ["{created}" => date("d.m.Y H:i", strtotime($invite["created"])), "{expires}" => date("d.m.Y H:i", $invite["expires_at"])]) }}
                            </small>
                        </div>
                        <button
                            class="btn btn-sm btn-outline-danger flex-shrink-0 z-organization-invite-revoke"
                            data-id="{{ $invite['id'] }}"
                            data-test="btn-revoke-organization-invite"
                        >
                            {{ __("organization.invites.revoke") }}
                        </button>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-muted" data-test="organization-invites-empty">{{ __("organization.invites.empty") }}</li>
            @endforelse
        </ul>

        @once
            <script>
                $(() => {
                    $(document).on("click", ".z-organization-invite-revoke", function() {
                        var button = $(this);
                        button.prop("disabled", true);

                        Z.Request.root("z/organization/revoke/" + button.data("id"), null, {}, (res) => {
                            if("success" == res.result) return location.reload();
                            button.prop("disabled", false);
                        });
                    });
                });
            </script>
        @endonce
    @endif
@endauth
