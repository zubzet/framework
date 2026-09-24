@extends($layout)

@section("content")
    @if(is_null(user()->orgId))
        <h2>{{ __("admin.organization.title") }}</h2>
        <p>{{ __("admin.organization.no_organization") }}</p>
    @else
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div class="mr-3">
                <small class="text-muted text-uppercase">{{ __("admin.organization.title") }}</small>
                <h2 class="mb-0 z-organization-name mt-2">
                    {{ $organizationName }}
                </h2>
            </div>
            <div class="mt-2">
                @if(user()->checkPermission("z.organization.rename"))
                    <button class="btn btn-outline-secondary" data-toggle="modal" data-target="#z-organization-rename-modal" data-test="btn-open-organization-rename">
                        <i class="fa fa-fw fa-pen"></i> {{ __("admin.organization.rename") }}
                    </button>
                @endif
                @if(user()->checkPermission("z.organization.invite"))
                    <button class="btn btn-primary" data-toggle="modal" data-target="#z-organization-invite-modal" data-test="btn-open-organization-invite">
                        <i class="fa fa-fw fa-user-plus text-white"></i> {{ __("admin.organization.invite") }}
                    </button>
                @endif
            </div>
        </div>

        <div class="row">
            @if(user()->checkPermission("z.organization.roles"))
                <div class="col-lg mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold"><i class="fa fa-fw fa-users"></i> {{ __("admin.organization.members") }}</span>
                            <span class="badge badge-pill badge-secondary">{{ count($members) }}</span>
                        </div>
                        <x-zubzet::organization.members :members="$members" :food="$roleFood" class="list-group-flush"/>
                        <div class="card-footer small text-muted">
                            {{ __("admin.organization.members_hint") }}
                        </div>
                    </div>
                </div>
            @endif

            @if(user()->checkPermission("z.organization.invite"))
                <div class="col-lg-5 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold"><i class="fa fa-fw fa-envelope-open-text"></i> {{ __("admin.organization.invites") }}</span>
                            <span class="badge badge-pill badge-secondary">{{ count($invites) }}</span>
                        </div>
                        <x-zubzet::organization.invites :invites="$invites" class="list-group-flush"/>
                        <div class="card-footer small text-muted">
                            {{ __("admin.organization.invites_hint") }}
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
                                {{ __("admin.organization.rename_title") }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __("admin.organization.close") }}">
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
                                {{ __("admin.organization.invite_title") }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __("admin.organization.close") }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">
                                {{ __("admin.organization.invite_hint") }}
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
