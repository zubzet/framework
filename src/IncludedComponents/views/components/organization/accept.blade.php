@props(["token"])

@auth
    <div {{ $attributes->class("z-organization-accept") }}>
        <button class="btn btn-primary z-organization-accept-button" data-token="{{ $token }}" data-test="btn-accept-organization-invitation">
            <i class="fa fa-fw fa-check text-white"></i> Accept invitation
        </button>
        <div class="small text-danger mt-2 d-none z-organization-accept-error" data-test="organization-accept-error"></div>
    </div>

    @once
        <script>
            $(() => {
                $(document).on("click", ".z-organization-accept-button", function() {
                    var button = $(this);
                    var error = button.closest(".z-organization-accept").find(".z-organization-accept-error");

                    button.prop("disabled", true);
                    error.addClass("d-none");

                    // attr() rather than data(), which would turn an all digit token into a number
                    Z.Request.root("z/organization/invitation/" + button.attr("data-token") + "/accept", null, {}, (res) => {
                        if("success" != res.result) {
                            button.prop("disabled", false);
                            // The backend answers with a code, Z.Lang holds its text
                            return error.text(Z.Lang["error_" + res.message] || Z.Lang.error_invalid_token).removeClass("d-none");
                        }

                        location.href = Z.Request.rootPath;
                    });
                });
            });
        </script>
    @endonce
@endauth
