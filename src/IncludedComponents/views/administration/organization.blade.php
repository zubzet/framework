@extends($layout)

@section("content")
    @if(is_null(user()->orgId))
        <h2>Organization</h2>
        <p>You are not a member of any organization.</p>
    @else
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div class="mr-3">
                <small class="text-muted text-uppercase">Organization</small>
                <h2 class="mb-0 z-organization-name mt-2">
                    {{ $organizationName }}
                </h2>
            </div>
            <div class="mt-2">
                @if(user()->checkPermission("z.organization.rename"))
                    <button class="btn btn-outline-secondary" data-toggle="modal" data-target="#z-organization-rename-modal" data-test="btn-open-organization-rename">
                        <i class="fa fa-fw fa-pen"></i> Rename
                    </button>
                @endif
                @if(user()->checkPermission("z.organization.invite"))
                    <button class="btn btn-primary" data-toggle="modal" data-target="#z-organization-invite-modal" data-test="btn-open-organization-invite">
                        <i class="fa fa-fw fa-user-plus text-white"></i> Invite member
                    </button>
                @endif
            </div>
        </div>

        <div class="row">
            @if(user()->checkPermission("z.organization.roles"))
                <div class="col-lg mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold"><i class="fa fa-fw fa-users"></i> Members</span>
                            <span class="badge badge-pill badge-secondary">{{ count($members) }}</span>
                        </div>
                        <x-zubzet::organization.members :members="$members" :food="$roleFood" class="list-group-flush"/>
                        <div class="card-footer small text-muted">
                            Open a member's roles to give them access or take it away again.
                        </div>
                    </div>
                </div>
            @endif

            @if(user()->checkPermission("z.organization.invite"))
                <div class="col-lg-5 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold"><i class="fa fa-fw fa-envelope-open-text"></i> Open invitations</span>
                            <span class="badge badge-pill badge-secondary">{{ count($invites) }}</span>
                        </div>
                        <x-zubzet::organization.invites :invites="$invites" class="list-group-flush"/>
                        <div class="card-footer small text-muted">
                            Invitations waiting to be accepted. Revoke one if it was sent by mistake.
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if(user()->checkPermission("z.organization.rename"))
            <div class="modal fade" id="z-organization-rename-modal" data-test="organization-rename-modal" tabindex="-1" aria-labelledby="z-organization-rename-title" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="z-organization-rename-title">
                                <i class="fa fa-pen mr-2"></i>
                                Rename organization
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <x-zubzet::organization.rename :name="$organizationName"/>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(user()->checkPermission("z.organization.invite"))
            <div class="modal fade" id="z-organization-invite-modal" data-test="organization-invite-modal" tabindex="-1" aria-labelledby="z-organization-invite-title" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="z-organization-invite-title">
                                <i class="fa fa-user-plus mr-2"></i>
                                Invite a member
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">
                                Enter an email address to create an invitation link and share it with that person.
                                The link stays valid for 7 days.
                            </p>
                            <x-zubzet::organization.invite/>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(() => {
                    // A created invitation belongs in the open invitations list
                    $("#z-organization-invite-modal").on("hidden.bs.modal", function() {
                        if(!$(this).find(".z-organization-invite-result").hasClass("d-none")) location.reload();
                    });
                });
            </script>
        @endif
    @endif
@endsection
