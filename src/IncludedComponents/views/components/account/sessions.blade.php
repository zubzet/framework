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
                            @if(is_null($session->lastUsed()))
                                never used since
                            @else
                                last seen {{ date("d.m.Y H:i", strtotime($session->lastUsed())) }} from {{ $session->ipLast() ?? "an unknown address" }}
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
                    <div class="flex-shrink-0">
                        <button
                            class="btn btn-sm btn-outline-secondary z-rename-session"
                            data-uuid="{{ $session->uuid() }}"
                            data-name="{{ $session->name() }}"
                            data-test="btn-rename-session"
                        >
                            Rename
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger z-revoke-session"
                            data-uuid="{{ $session->uuid() }}"
                            data-test="btn-revoke-session"
                        >
                            Revoke
                        </button>
                    </div>
                </div>
            </li>
        @empty
            <li class="list-group-item text-muted" data-test="sessions-empty">No sessions.</li>
        @endforelse
    </ul>

    <div class="modal fade" id="z-session-rename" data-test="session-rename" tabindex="-1" role="dialog" aria-labelledby="z-session-rename-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="z-session-rename-title">
                        <i class="fa fa-fw fa-pen mr-1"></i> Rename session
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        An empty name hands the session back to its device name.
                    </p>
                    <input class="form-control z-session-name" data-test="session-name" maxlength="255" placeholder="Name, e.g. Laptop at home">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary z-session-rename-save" data-test="btn-save-session-name">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(() => {
            var rename = $("#z-session-rename");

            $("#z-sessions").on("click", ".z-revoke-session", function() {
                Z.Request.root("z/profile", "revoke-session", {
                    uuid: $(this).data("uuid")
                }, (res) => {
                    if("success" == res.result) location.reload();
                });
            });

            $("#z-sessions").on("click", ".z-rename-session", function() {
                rename.data("uuid", $(this).data("uuid"));
                rename.find(".z-session-name").val($(this).attr("data-name"));
                rename.modal("show");
            });

            rename.on("click", ".z-session-rename-save", () => {
                Z.Request.root("z/profile", "rename-session", {
                    uuid: rename.data("uuid"),
                    name: rename.find(".z-session-name").val()
                }, (res) => {
                    if("success" == res.result) location.reload();
                });
            });
        });
    </script>
@endauth
