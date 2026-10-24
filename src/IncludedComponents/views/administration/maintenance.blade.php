@extends($layout)

@section("content")
    <h2 data-test="maintenance-heading"><?= e(__("admin.maintenance.title")) ?></h2>

    <div class="row text-center">
        <div class="col-12 col-lg-4">
            <div class="card mb-3 mt-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">
                        <?= e(__("admin.maintenance.state")) ?>
                    </small>
                    <strong class="text-primary" data-test="maintenance-status">
                        <?= e($opt["isActive"] ? __("admin.maintenance.state_maintenance") : __("admin.maintenance.state_normal")) ?>
                    </strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card mb-3 mt-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">
                        <?= e(__("admin.maintenance.mode")) ?>
                    </small>
                    <strong data-test="maintenance-mode">
                        <?= e($opt["mode"]); ?>
                    </strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card mb-3 mt-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">
                        <?= e(__("admin.maintenance.browser")) ?>
                    </small>
                    <strong data-test="maintenance-browser">
                        <?= e($opt["browserCanBypass"] ? __("admin.maintenance.can_bypass") : __("admin.maintenance.cannot_bypass")) ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <div class="my-3 d-flex justify-content-center">
        <button class="btn btn-primary border mx-3 shadow-sm" data-test="btn-bypass-maintenance" id="bypass-maintenance" <?= $opt["browserCanBypass"] ? "disabled" : "" ?>>
            <i class="fas fa-shield-alt mr-2"></i>
            <?= e(__("admin.maintenance.bypass")) ?>
            <?= $opt["browserCanBypass"] ? e(__("admin.maintenance.bypass_set")) : "" ?>
        </button>
    </div>

    <script>
        $("#bypass-maintenance").click(() => {
            Z.Request.action("bypass-maintenance", {}, (res) => {
                if("success" == res.result) {
                    location.reload();
                    return;
                }
                alert(<?= json_encode(__("admin.maintenance.bypass_failed")) ?>);
            });
        });
    </script>
@endsection
