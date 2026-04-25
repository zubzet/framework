<?php return ["body" => function($opt) { ?>

    <h2>Maintenance Mode</h2>

    <div class="card mb-3 mt-3">
        <div class="card-body py-2 px-3">
            <div class="row text-center">
                <div class="col border-right">
                    <small class="text-muted d-block">Status</small>
                    <strong class="text-danger">
                        <?= $opt["isActive"] ? "Active" : "Inactive"; ?>
                    </strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Modus</small>
                    <strong><?= $opt["mode"]; ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="my-3 d-flex justify-content-center">
        <button class="btn btn-light border mx-3" id="bypass-maintenance" type="button">
            <i class="fas fa-shield-alt mr-2 text-muted"></i>
            Bypass Cookie
        </button>
    </div>

    <script>
        $("#bypass-maintenance").click(function() {
            Z.Request.action("bypass-maintenance", {}, (res) => {
                if(res.result == "success") {
                    alert("Bypass cookie set successfully. It will expire in 1 hour.");
                    return;
                }
                alert("Failed to set bypass cookie");
            });
        });
    </script>

<?php }]; ?>