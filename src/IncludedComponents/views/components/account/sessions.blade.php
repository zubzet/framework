@php
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Authentication\Permission\User;
@endphp

@auth
    @php
        $currentUuid = Session::byToken(user()->getSessionToken())?->uuid();
    @endphp

    <ul id="z-sessions" {{ $attributes->class("list-group") }}>
        @forelse(Session::byUser(User::byId(user()->userId)) as $session)
            <li class="list-group-item" data-test="session-{{ $session->uuid() }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="mr-3">
                        <div>
                            {{ $session->name() ?? __("account.sessions.fallback_name") }}
                            @if($session->uuid() === $currentUuid)
                                <span class="badge badge-success" data-test="session-current">{{ __("account.sessions.current") }}</span>
                            @endif
                        </div>
                        <small class="text-muted d-block">
                            {{ $session->device() ?? __("account.sessions.unknown_device") }}
                        </small>
                        <small class="text-muted d-block">
                            {{ __("account.sessions.started", ["{date}" => date("d.m.Y H:i", strtotime($session->created())), "{ip}" => $session->ipCreation() ?? __("account.unknown_address")]) }}
                            @if(is_null($session->lastUsed()))
                                {{ __("account.never_used") }}
                            @else
                                {{ __("account.last_seen", ["{date}" => date("d.m.Y H:i", strtotime($session->lastUsed())), "{ip}" => $session->ipLast() ?? __("account.unknown_address")]) }}
                            @endif
                        </small>
                        <small class="text-muted d-block">
                            @if(is_null($session->expiresAt()))
                                {{ __("account.never_expires") }}
                            @else
                                {{ __("account.expires", ["{date}" => date("d.m.Y H:i", strtotime($session->expiresAt()))]) }}
                            @endif
                        </small>
                        @if(!is_null($session->reason()))
                            <small class="text-muted d-block">{{ __("account.sessions.reason", ["{reason}" => $session->reason()]) }}</small>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        <button
                            class="btn btn-sm btn-outline-secondary z-rename-token"
                            data-uuid="{{ $session->uuid() }}"
                            data-name="{{ $session->name() }}"
                            data-type="session"
                            data-placeholder="{{ __("account.sessions.name_placeholder") }}"
                            data-test="btn-rename-session"
                        >
                            {{ __("account.rename") }}
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger z-revoke-session"
                            data-uuid="{{ $session->uuid() }}"
                            data-test="btn-revoke-session"
                        >
                            {{ __("account.revoke") }}
                        </button>
                    </div>
                </div>
            </li>
        @empty
            <li class="list-group-item text-muted" data-test="sessions-empty">{{ __("account.sessions.empty") }}</li>
        @endforelse
    </ul>

    <x-zubzet::account.rename-token/>

    <script>
        $(() => {
            $("#z-sessions").on("click", ".z-revoke-session", function() {
                Z.Request.root("_zubzet/profile/revoke-token", null, {
                    uuid: $(this).data("uuid"),
                    type: "session"
                }, (res) => {
                    if("success" == res.result) location.reload();
                });
            });
        });
    </script>
@endauth
