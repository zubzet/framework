@extends($layout)

@section("content")
    <h2 class="mb-4" data-test="profile-heading">Profile</h2>

    <div class="card mb-4">
        <div class="card-header font-weight-bold">
            <i class="fa fa-fw fa-user mr-1"></i> Your data
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Email</dt>
                <dd class="col-sm-8" data-test="profile-email">
                    {{ $account->email() }}
                </dd>

                <dt class="col-sm-4">User</dt>
                <dd class="col-sm-8">
                    <code data-test="profile-uuid">
                        {{ $account->uuid() }}
                    </code>
                </dd>

                <dt class="col-sm-4">Member since</dt>
                <dd class="col-sm-8" data-test="profile-created">
                    {{ date("d.m.Y", strtotime($account->getField("created"))) }}
                </dd>

                @if(!is_null($account->organization()))
                    <dt class="col-sm-4">Organization</dt>
                    <dd class="col-sm-8" data-test="profile-organization">
                        {{ $account->organization()->name() }}
                    </dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header font-weight-bold">
            <i class="fa fa-fw fa-key mr-1"></i> Change password
        </div>
        <div class="card-body">
            <div class="small text-muted mb-3">
                A new password ends every session of this account. This browser stays
                signed in, every other one has to authenticate again. Api keys keep working.
            </div>
            <div id="password-form" data-test="password-form"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">
                <i class="fa fa-fw fa-desktop mr-1"></i> Sessions
            </span>
            <button class="btn btn-sm btn-outline-danger" id="clear-sessions" data-test="btn-clear-sessions">
                <i class="fa fa-fw fa-sign-out-alt text-danger"></i>
                Clear all sessions
            </button>
        </div>
        <ul class="list-group list-group-flush">
            @forelse($sessions as $session)
                <li class="list-group-item" data-test="session-{{ $session->uuid() }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="mr-3">
                            <div>
                                {{ $session->name() ?? "Session" }}
                                @if($session->uuid() === $currentUuid)
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
                            class="btn btn-sm btn-outline-danger flex-shrink-0 revoke-token"
                            data-type="session"
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
    </div>

    <div class="card mb-4">
        <div class="card-header font-weight-bold"><i class="fa fa-fw fa-code mr-1"></i> API keys</div>
        <div class="card-body">
            <div class="input-group">
                <input class="form-control" id="api-key-name" data-test="api-key-name" placeholder="Name, e.g. Deployment pipeline">
                <select class="custom-select flex-grow-0 w-auto" id="api-key-lifetime" data-test="api-key-lifetime">
                    @foreach($apiKeyLifetimes as $days => $label)
                        <option value="{{ $days }}">Expires in {{ $label }}</option>
                    @endforeach
                    <option value="never">Never expires</option>
                </select>
                <div class="input-group-append">
                    <button class="btn btn-primary" id="create-api-key" data-test="btn-create-api-key">
                        <i class="fa fa-fw fa-plus text-white"></i> Create api key
                    </button>
                </div>
            </div>
        </div>
        <ul class="list-group list-group-flush">
            @forelse($apiKeys as $apiKey)
                <li class="list-group-item" data-test="api-key-{{ $apiKey->uuid() }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="mr-3">
                            <div>
                                {{ $apiKey->name() ?? "API key" }}
                                @if($apiKey->uuid() === $currentUuid)
                                    <span class="badge badge-success" data-test="api-key-current">In use right now</span>
                                @endif
                            </div>
                            <small class="text-muted d-block">
                                Created {{ date("d.m.Y H:i", strtotime($apiKey->created())) }} from {{ $apiKey->ipCreation() ?? "an unknown address" }},
                                @if(is_null($apiKey->ipLast()))
                                    never used since
                                @else
                                    last seen from {{ $apiKey->ipLast() }}
                                @endif
                            </small>
                            <small class="text-muted d-block">
                                @if(is_null($apiKey->expiresAt()))
                                    Never expires
                                @else
                                    Expires {{ date("d.m.Y H:i", strtotime($apiKey->expiresAt())) }}
                                @endif
                            </small>
                        </div>
                        <button
                            class="btn btn-sm btn-outline-danger flex-shrink-0 revoke-token"
                            data-type="api"
                            data-uuid="{{ $apiKey->uuid() }}"
                            data-test="btn-revoke-api-key"
                        >
                            Revoke
                        </button>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-muted" data-test="api-keys-empty">No api keys.</li>
            @endforelse
        </ul>
    </div>

    <div class="modal fade" id="api-key-created" data-test="api-key-created" tabindex="-1" role="dialog" aria-labelledby="api-key-created-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="api-key-created-title">
                        <i class="fa fa-fw fa-code mr-1"></i> Your new api key
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Shown once. Copy it now, it cannot be read again.
                    </p>
                    <input class="form-control" id="api-key-token" data-test="api-key-token" readonly>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal" data-test="btn-api-key-done">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(() => {
            var passwordForm = Z.Forms.create({
                dom: "password-form",
                name: "password",
                doReload: true,
            });

            passwordForm.createField({
                name: "password_current",
                type: "password",
                text: "Current password",
                required: true
            });

            passwordForm.createField({
                name: "password_new",
                type: "password",
                text: "New password",
                required: true
            });

            passwordForm.createField({
                name: "password_repeat",
                type: "password",
                text: "Repeat new password",
                required: true
            });

            $("#clear-sessions").click(() => {
                Z.Request.action('clear-sessions', {}, (res) => {
                    if('success' == res.result) {
                        location.reload()
                        return;
                    }
                });
            });

            $(".revoke-token").click(function() {
                Z.Request.action("revoke-token", {
                    uuid: $(this).data("uuid"),
                    type: $(this).data("type")
                }, (res) => {
                    if('success' == res.result) {
                        location.reload()
                        return;
                    }
                });

            });

            $("#create-api-key").click(() => {
                Z.Request.action("create-api-key", {
                    name: $("#api-key-name").val(),
                    lifetime: $("#api-key-lifetime").val()
                }, (res) => {
                    if("success" == res.result) {
                        $("#api-key-token").val(res.token);
                        $("#api-key-created").modal("show");
                        $("#api-key-name").val("");
                        return;
                    }
                });
            });

            $("#api-key-created").on("hidden.bs.modal", () => location.reload());
        });
    </script>
@endsection
