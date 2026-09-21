<style>
    .z-two-factor-digit {
        width: 3rem;
    }
</style>

<div
    class="modal"
    id="z-two-factor-modal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="z-two-factor-modal-title"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center px-4 pt-4 pb-2">
                <i class="fa fa-shield-alt fa-2x text-primary mb-3"></i>

                <h5 class="mb-1" id="z-two-factor-modal-title">Two factor</h5>
                <p class="text-muted small mb-4">
                    Your password was accepted. Enter the code from your authenticator.
                </p>

                <div class="d-flex justify-content-center" id="z-two-factor-digits">
                    @for($digit = 0; $digit < 6; $digit++)
                        <input
                            class="form-control form-control-lg text-center font-weight-bold mx-1 z-two-factor-digit"
                            type="text"
                            inputmode="numeric"
                            autocomplete="{{ 0 === $digit ? "one-time-code" : "off" }}"
                            maxlength="1"
                        >
                    @endfor
                </div>

                <div class="text-danger small mt-3 d-none" id="z-two-factor-modal-error"></div>
            </div>

            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-link text-muted" id="z-two-factor-modal-cancel">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary px-4" id="z-two-factor-modal-send">
                    Sign in
                </button>
            </div>
        </div>
    </div>
</div>
