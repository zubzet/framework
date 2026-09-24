@auth
    {{-- The sessions and the api keys both bring it, one modal serves either --}}
    @once
        <div class="modal fade" id="z-rename-token" data-test="token-rename" tabindex="-1" role="dialog" aria-labelledby="z-rename-token-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="z-rename-token-title">
                            <i class="fa fa-fw fa-pen mr-1"></i> {{ __("account.rename_token.title") }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __("account.close") }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            {{ __("account.rename_token.hint") }}
                        </p>
                        <input class="form-control z-token-name" data-test="token-name" maxlength="255" placeholder="{{ __("account.rename_token.placeholder") }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary z-token-rename-save" data-test="btn-save-token-name">
                            {{ __("account.rename_token.save") }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(() => {
                var rename = $("#z-rename-token");

                // Delegated from the document, so a button of either component is caught
                $(document).on("click", ".z-rename-token", function() {
                    rename.data("uuid", $(this).data("uuid"));
                    rename.data("type", $(this).data("type"));
                    rename.find(".z-token-name")
                        .attr("placeholder", $(this).data("placeholder") || <?= json_encode(__("account.rename_token.placeholder")) ?>)
                        .val($(this).attr("data-name"));
                    rename.modal("show");
                });

                rename.on("click", ".z-token-rename-save", () => {
                    Z.Request.root("_zubzet/profile/rename-token", null, {
                        uuid: rename.data("uuid"),
                        type: rename.data("type"),
                        name: rename.find(".z-token-name").val()
                    }, (res) => {
                        if("success" == res.result) location.reload();
                    });
                });
            });
        </script>
    @endonce
@endauth
