<?php return ["body" => function($opt) { ?>

    <h2>Maintenance Mode</h2>

    <div class="row text-center">
        <div class="col-12 col-lg-4">
            <div class="card mb-3 mt-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">
                        Currently Active
                    </small>
                    <strong class="text-primary">
                        <?= $opt["isActive"] ? "Maintenance" : "Normal" ?>
                    </strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card mb-3 mt-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">
                        Current Mode
                    </small>
                    <strong>
                        <?= e($opt["mode"]); ?>
                    </strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card mb-3 mt-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">
                        Your Browser Status
                    </small>
                    <strong>
                        <?= $opt["browserCanBypass"] ? "Can Bypass" : "Cannot Bypass"; ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <div class="my-3 d-flex justify-content-center">
        <button class="btn btn-primary border mx-3 shadow-sm" id="bypass-maintenance" <?= $opt["browserCanBypass"] ? "disabled" : "" ?>>
            <i class="fas fa-shield-alt mr-2"></i>
            Bypass Cookie
            <?= $opt["browserCanBypass"] ? "(Already set)" : "" ?>
        </button>
    </div>

    <script>
        $("#bypass-maintenance").click(() => {
            Z.Request.action("bypass-maintenance", {}, (res) => {
                if("success" == res.result) {
                    location.reload();
                    return;
                }
                alert("Failed to set bypass cookie");
            });
        });
    </script>

<?php }]; ?>