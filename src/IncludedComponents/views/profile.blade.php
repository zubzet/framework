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
            <x-zubzet::account.change-password/>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">
                <i class="fa fa-fw fa-desktop mr-1"></i> Sessions
            </span>
            <x-zubzet::account.clear-sessions class="btn btn-sm btn-outline-danger">
                <i class="fa fa-fw fa-sign-out-alt text-danger"></i>
                Clear all sessions
            </x-zubzet::account.clear-sessions>
        </div>
        <x-zubzet::account.sessions class="list-group-flush"/>
    </div>

    <div class="card mb-4">
        <div class="card-header font-weight-bold"><i class="fa fa-fw fa-code mr-1"></i> API keys</div>
        <div class="card-body">
            <x-zubzet::account.api-keys/>
        </div>
    </div>
@endsection
