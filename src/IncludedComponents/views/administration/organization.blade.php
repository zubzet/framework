@extends($layout)

@section("content")
    <h2>Organization</h2>

    @if(is_null(user()->orgId))
        <p>You are not a member of any organization.</p>
    @else
        @if(user()->checkPermission("z.organization.rename"))
            <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
                <i class="fa fa-fw fa-pen"></i>
                Name
            </h2>
            <div class="card shadow-sm">
                <div class="card-body py-2 px-3">
                    <p class="small text-muted mb-3">
                        The name of your organization, as its members and invited people see it.
                    </p>
                    <x-zubzet::organization.rename :name="$organizationName"/>
                </div>
            </div>
        @endif

        @if(user()->checkPermission("z.organization.invite"))
            <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
                <i class="fa fa-fw fa-user-plus"></i>
                Invite a member
            </h2>
            <div class="card shadow-sm">
                <div class="card-body py-2 px-3">
                    <p class="small text-muted mb-3">
                        Enter an email address to create an invitation link and share it with that person.
                        The link stays valid for 7 days and can only be accepted with that email address.
                    </p>
                    <x-zubzet::organization.invite/>
                </div>
            </div>
        @endif

        @if(user()->checkPermission("z.organization.roles"))
            <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
                <i class="fa fa-fw fa-users"></i>
                Members
            </h2>
            <div class="card shadow-sm">
                <div class="card-body py-2 px-3">
                    <p class="small text-muted mb-0">
                        Give the members of your organization roles or take them away again.
                        Only roles released to organizations can be assigned here.
                    </p>
                </div>
                <x-zubzet::organization.members :members="$members" :food="$roleFood" class="list-group-flush border-top"/>
            </div>
        @endif

        @if(user()->checkPermission("z.organization.invite"))
            <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
                <i class="fa fa-fw fa-envelope-open-text"></i>
                Open invitations
            </h2>
            <div class="card shadow-sm mb-3">
                <div class="card-body py-2 px-3">
                    <p class="small text-muted mb-0">
                        Invitations that have not been accepted yet. Expired ones are no longer listed,
                        and revoking one makes its link stop working right away.
                    </p>
                </div>
                <x-zubzet::organization.invites :invites="$invites" class="list-group-flush border-top"/>
            </div>
        @endif
    @endif
@endsection
