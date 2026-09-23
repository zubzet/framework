@extends($layout)

@section("content")
    <div class="container my-5" style="max-width: 720px">
        <h2 data-test="invitation-heading">Invitation</h2>

        <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5" data-test="invitation-organization">
            <i class="fa fa-fw fa-building"></i>
            {{ $organizationName }}
        </h2>
        <div class="card shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="small text-muted mb-3">
                    You have been invited to join this organization. Accepting makes your
                    account a member of it.
                </div>

                <dl class="row mb-3">
                    <dt class="col-sm-4">Invited as</dt>
                    <dd class="col-sm-8" data-test="invitation-email">
                        {{ $invite["email"] }}
                    </dd>

                    <dt class="col-sm-4">Invited on</dt>
                    <dd class="col-sm-8 mb-0" data-test="invitation-created">
                        {{ date("d.m.Y H:i", strtotime($invite["created"])) }}
                    </dd>
                </dl>

                <x-zubzet::organization.accept :token="$invite['token']" class="mb-2"/>
            </div>
        </div>
    </div>
@endsection
