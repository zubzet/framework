@extends($layout)

@section("content")
    <div class="mb-3">
        <small class="text-muted text-uppercase">Profile</small>
        <h2 class="mb-1 text-break" data-test="profile-heading">
            <span data-test="profile-email">{{ $account->email() }}</span>
        </h2>
        <div class="text-muted small">
            <span class="mr-3">
                <i class="fa fa-fw fa-calendar-alt"></i>
                Member since <span data-test="profile-created">{{ date("d.m.Y", strtotime($account->getField("created"))) }}</span>
            </span>
            @if(!is_null($account->organization()))
                <span>
                    <i class="fa fa-fw fa-building"></i>
                    <span data-test="profile-organization">{{ $account->organization()->name() }}</span>
                </span>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header font-weight-bold">
            <i class="fa fa-fw fa-shield-alt"></i> Security
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="mr-3">
                        <div>
                            <i class="fa fa-fw fa-key text-muted"></i>
                            Password
                        </div>
                        <small class="text-muted d-block">
                            A new password ends every login of this account, api keys keep working.
                        </small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary flex-shrink-0" data-toggle="modal" data-target="#z-change-password-modal" data-test="btn-open-change-password">
                        Change
                    </button>
                </div>
            </li>
            <li class="list-group-item">
                <x-zubzet::account.two-factor/>
            </li>
        </ul>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="font-weight-bold"><i class="fa fa-fw fa-desktop"></i> Sessions</span>
            <x-zubzet::account.clear-sessions class="btn btn-sm btn-outline-danger">
                <i class="fa fa-fw fa-sign-out-alt"></i>
                Clear all sessions
            </x-zubzet::account.clear-sessions>
        </div>
        <x-zubzet::account.sessions class="list-group-flush"/>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header font-weight-bold">
            <i class="fa fa-fw fa-code"></i> API keys
        </div>
        <div class="card-body">
            <x-zubzet::account.api-keys/>
        </div>
    </div>

    <div class="modal fade" id="z-change-password-modal" data-test="change-password-modal" tabindex="-1" aria-labelledby="z-change-password-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="z-change-password-title">
                        <i class="fa fa-fw fa-key mr-1"></i> Change password
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        A new password ends every login of this account, this browser included,
                        so it has to authenticate again. Api keys keep working.
                    </p>
                    <x-zubzet::account.change-password/>
                </div>
            </div>
        </div>
    </div>
@endsection
