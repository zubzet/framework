@extends($layout)

@section("content")
    <h2 data-test="profile-heading">Profile</h2>

    <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
        <i class="fa fa-fw fa-user"></i>
        Your data
    </h2>
    <div class="card shadow-sm">
        <div class="card-body py-2 px-3">
            <dl class="row mb-0">
                <dt class="col-sm-4">Email</dt>
                <dd class="col-sm-8" data-test="profile-email">
                    {{ $account->email() }}
                </dd>

                <dt class="col-sm-4">Member since</dt>
                <dd class="col-sm-8" data-test="profile-created">
                    {{ date("d.m.Y", strtotime($account->getField("created"))) }}
                </dd>

                @if(!is_null($account->organization()))
                    <dt class="col-sm-4">Organization</dt>
                    <dd class="col-sm-8 mb-0" data-test="profile-organization">
                        {{ $account->organization()->name() }}
                    </dd>
                @endif
            </dl>
        </div>
    </div>

    <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
        <i class="fa fa-fw fa-key"></i>
        Change password
    </h2>
    <div class="card shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="small text-muted mb-3">
                A new password ends every login of this account, this browser included,
                so it has to authenticate again. Api keys keep working.
            </div>
            <x-zubzet::account.change-password/>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h2 class="mb-0 pl-1 font-weight-bold h5">
            <i class="fa fa-fw fa-desktop"></i>
            Sessions
        </h2>
        <x-zubzet::account.clear-sessions class="btn btn-sm btn-outline-danger">
            <i class="fa fa-fw fa-sign-out-alt"></i>
            Clear all sessions
        </x-zubzet::account.clear-sessions>
    </div>
    <div class="card shadow-sm">
        <x-zubzet::account.sessions class="list-group-flush"/>
    </div>

    <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
        <i class="fa fa-fw fa-code"></i>
        API keys
    </h2>
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <x-zubzet::account.api-keys/>
        </div>
    </div>
@endsection
