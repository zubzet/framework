@php
    use ZubZet\Framework\Authentication\APIKey;
    use ZubZet\Framework\Authentication\Session;
    use ZubZet\Framework\Authentication\Permission\User;
@endphp

@auth
    <div id="z-api-keys" {{ $attributes }}>
        <div class="input-group">
            <input class="form-control z-api-key-name" data-test="api-key-name" maxlength="255" placeholder="Name, e.g. Deployment pipeline">
            <select class="custom-select flex-grow-0 w-auto z-api-key-lifetime" data-test="api-key-lifetime">
                @foreach(APIKey::LIFETIMES as $days => $label)
                    <option value="{{ $days }}">Expires in {{ $label }}</option>
                @endforeach
                <option value="never">Never expires</option>
            </select>
            <div class="input-group-append">
                <button class="btn btn-primary z-api-key-create" data-test="btn-create-api-key">
                    <i class="fa fa-fw fa-plus text-white"></i> Create api key
                </button>
            </div>
        </div>

        <ul class="list-group mt-3">
            @forelse(APIKey::byUser(User::byId(user()->userId)) as $apiKey)
                <li class="list-group-item" data-test="api-key-{{ $apiKey->uuid() }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="mr-3">
                            <div>
                                {{ $apiKey->name() ?? "API key" }}
                            </div>
                            <small class="text-muted d-block">
                                Created {{ date("d.m.Y H:i", strtotime($apiKey->created())) }} from {{ $apiKey->ipCreation() ?? "an unknown address" }},
                                @if(is_null($apiKey->lastUsed()))
                                    never used since
                                @else
                                    last seen {{ date("d.m.Y H:i", strtotime($apiKey->lastUsed())) }} from {{ $apiKey->ipLast() ?? "an unknown address" }}
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
                        <div class="flex-shrink-0">
                            <button
                                class="btn btn-sm btn-outline-secondary z-rename-token"
                                data-uuid="{{ $apiKey->uuid() }}"
                                data-name="{{ $apiKey->name() }}"
                                data-type="api-key"
                                data-placeholder="Name, e.g. Deployment pipeline"
                                data-test="btn-rename-api-key"
                            >
                                Rename
                            </button>
                            <button
                                class="btn btn-sm btn-outline-danger z-revoke-api-key"
                                data-uuid="{{ $apiKey->uuid() }}"
                                data-test="btn-revoke-api-key"
                            >
                                Revoke
                            </button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-muted" data-test="api-keys-empty">No api keys.</li>
            @endforelse
        </ul>

        <div class="modal fade z-api-key-created" data-test="api-key-created" tabindex="-1" role="dialog" aria-labelledby="z-api-keys-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="z-api-keys-title">
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
                        <div class="input-group">
                            <input class="form-control z-api-key-token" data-test="api-key-token" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary z-api-key-copy" data-test="btn-copy-api-key">
                                    <i class="fa fa-fw fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal" data-test="btn-api-key-done">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-zubzet::account.rename-token/>

    <script>
        $(() => {
            var root = $("#z-api-keys");

            root.on("click", ".z-api-key-create", () => {
                Z.Request.root("_zubzet/profile/create-api-key", null, {
                    name: root.find(".z-api-key-name").val(),
                    lifetime: root.find(".z-api-key-lifetime").val()
                }, (res) => {
                    if("success" == res.result) {
                        root.find(".z-api-key-token").val(res.token);
                        root.find(".z-api-key-created").modal("show");
                        root.find(".z-api-key-name").val("");
                    }
                });
            });

            root.on("click", ".z-api-key-copy", function() {
                var token = root.find(".z-api-key-token");
                navigator.clipboard.writeText(token.val());
                $(this).html('<i class="fa fa-fw fa-check"></i> Copied');
            });

            root.on("click", ".z-revoke-api-key", function() {
                Z.Request.root("_zubzet/profile/revoke-token", null, {
                    uuid: $(this).data("uuid"),
                    type: "api-key"
                }, (res) => {
                    if("success" == res.result) location.reload();
                });
            });

            root.find(".z-api-key-created").on("hidden.bs.modal", () => location.reload());
        });
    </script>
@endauth
