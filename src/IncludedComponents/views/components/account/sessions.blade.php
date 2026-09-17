@php
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Authentication\Permission\User;
@endphp

@auth
    <ul id="z-sessions" {{ $attributes->class("list-group") }}>
        @forelse(Session::byUser(User::byId(user()->userId)) as $session)
            <li class="list-group-item" data-test="session-{{ $session->uuid() }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="mr-3">
                        <div>
                            {{ $session->name() ?? "Session" }}
                            @if($session->uuid() === Session::byToken(user()->getSessionToken())?->uuid())
                                <span class="badge badge-success" data-test="session-current">This browser</span>
                            @endif
                        </div>
                        <small class="text-muted d-block">
                            {{ $session->device() ?? "Unknown device" }}
                        </small>
                        <small class="text-muted d-block">
                            Started {{ date("d.m.Y H:i", strtotime($session->created())) }} from {{ $session->ipCreation() ?? "an unknown address" }},
                            @if(is_null($session->ipLast()))
                                never used since
                            @else
                                last seen from {{ $session->ipLast() }}
                            @endif
                        </small>
                        <small class="text-muted d-block">
                            @if(is_null($session->expiresAt()))
                                Never expires
                            @else
                                Expires {{ date("d.m.Y H:i", strtotime($session->expiresAt())) }}
                            @endif
                        </small>
                        @if(!is_null($session->reason()))
                            <small class="text-muted d-block">Reason: {{ $session->reason() }}</small>
                        @endif
                    </div>
                    <button
                        class="btn btn-sm btn-outline-danger flex-shrink-0 z-revoke-session"
                        data-uuid="{{ $session->uuid() }}"
                        data-test="btn-revoke-session"
                    >
                        Revoke
                    </button>
                </div>
            </li>
        @empty
            <li class="list-group-item text-muted" data-test="sessions-empty">No sessions.</li>
        @endforelse
    </ul>

    <script>
        $(() => {
            $("#z-sessions").on("click", ".z-revoke-session", function() {
                Z.Request.root("profile/revoke-session", null, {
                    uuid: $(this).data("uuid")
                }, (res) => {
                    if("success" == res.result) location.reload();
                });
            });
        });
    </script>
@endauth
