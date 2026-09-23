@php
    use ZubZet\Framework\Authentication\Permission\User;
@endphp

@auth
    @php
        $account = User::byId(user()->userId);
    @endphp

    <script src="<?= zubzet()->rootFolder . "_zubzet/asset-proxy/qrcode.js" ?>"></script>

    <div id="z-two-factor" {{ $attributes }}>
        @if($account->hasTwoFactor())
            <div class="d-flex justify-content-between align-items-start">
                <div class="mr-3">
                    <div data-test="two-factor-active">
                        <i class="fa fa-fw fa-shield-alt text-success"></i>
                        Two factor is on
                    </div>
                    <small class="text-muted d-block">
                        Confirmed {{ date("d.m.Y H:i", strtotime($account->twoFactorConfirmedAt())) }}
                    </small>
                </div>
                <button class="btn btn-sm btn-outline-danger flex-shrink-0 z-two-factor-disable" data-test="btn-disable-two-factor">
                    Turn off
                </button>
            </div>

            <div class="input-group mt-3 d-none z-two-factor-disable-form" data-test="two-factor-disable-form">
                <input
                    class="form-control z-two-factor-disable-code"
                    data-test="two-factor-disable-code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="Code from your authenticator"
                >
                <div class="input-group-append">
                    <button class="btn btn-danger z-two-factor-disable-confirm" data-test="btn-confirm-disable-two-factor">
                        Turn off
                    </button>
                </div>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-start">
                <div class="mr-3">
                    <div data-test="two-factor-inactive">
                        <i class="fa fa-fw fa-shield-alt text-muted"></i>
                        Two factor is off
                    </div>
                    <small class="text-muted d-block">
                        A login then also asks for a code from an authenticator app.
                    </small>
                </div>
                <button class="btn btn-sm btn-outline-secondary flex-shrink-0 z-two-factor-start" data-test="btn-start-two-factor">
                    Set up
                </button>
            </div>
        @endif

        @if(!$account->hasTwoFactor())
        <div class="d-none mt-3 z-two-factor-setup" data-test="two-factor-setup">
            <div class="small text-muted mb-3">
                Scan this with your authenticator, then type the code it shows to
                confirm it arrived. Two factor stays off until you do.
            </div>
            <div class="d-flex flex-wrap">
                <div class="mr-3 mb-3 z-two-factor-qr" data-test="two-factor-qr"></div>
                <div>
                    <label class="small text-muted mb-1">Or type the key by hand</label>
                    <input class="form-control mb-3 z-two-factor-secret" data-test="two-factor-secret" readonly>
                    <div class="input-group">
                        <input
                            class="form-control z-two-factor-code"
                            data-test="two-factor-code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="6 digit code"
                        >
                        <div class="input-group-append">
                            <button class="btn btn-primary z-two-factor-confirm" data-test="btn-confirm-two-factor">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="small text-danger mt-2 d-none z-two-factor-error" data-test="two-factor-error"></div>
    </div>

    <script>
        $(() => {
            var root = $("#z-two-factor");
            var error = root.find(".z-two-factor-error");

            function fail(message) {
                error.text(message).removeClass("d-none");
            }

            function renderQr(uri) {
                new QRCode(root.find(".z-two-factor-qr")[0], {
                    text: uri,
                    width: 180,
                    height: 180,
                    correctLevel: QRCode.CorrectLevel.L
                });
            }

            root.on("click", ".z-two-factor-start", function() {
                var button = $(this);
                button.prop("disabled", true);
                error.addClass("d-none");

                Z.Request.root("_zubzet/profile/start-two-factor", null, {}, (res) => {
                    // Stays disabled once it worked, a second run would stack another qr code
                    if("success" != res.result) {
                        button.prop("disabled", false);
                        return fail(res.message);
                    }

                    root.find(".z-two-factor-secret").val(res.secret);
                    root.find(".z-two-factor-setup").removeClass("d-none");
                    renderQr(res.uri);
                });
            });

            root.on("click", ".z-two-factor-confirm", () => {
                error.addClass("d-none");

                Z.Request.root("_zubzet/profile/confirm-two-factor", null, {
                    code: root.find(".z-two-factor-code").val()
                }, (res) => {
                    if("success" != res.result) return fail(res.message);
                    location.reload();
                });
            });

            root.on("click", ".z-two-factor-disable", () => {
                error.addClass("d-none");
                root.find(".z-two-factor-disable-form").removeClass("d-none");
                root.find(".z-two-factor-disable-code").focus();
            });

            root.on("click", ".z-two-factor-disable-confirm", () => {
                error.addClass("d-none");

                Z.Request.root("_zubzet/profile/disable-two-factor", null, {
                    code: root.find(".z-two-factor-disable-code").val()
                }, (res) => {
                    if("success" != res.result) return fail(res.message);
                    location.reload();
                });
            });
        });
    </script>
@endauth
