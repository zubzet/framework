@extends($layout)

@section("content")
    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            form.createField({
                name: "name",
                type: "text",
                text: <?= json_encode(__("admin.organizations.name")) ?>,
                required: true,
                value: <?= json_encode($opt["name"], JSON_HEX_TAG | JSON_HEX_AMP); ?>,
            });

            form.createField({
                name: "max_capacity",
                type: "number",
                text: <?= json_encode(__("admin.organizations.max_capacity")) ?>,
                hint: <?= json_encode(__("admin.organizations.max_capacity_hint")) ?>,
                value: <?= json_encode($opt["maxCapacity"]); ?>,
            });

            <?php if($opt["user"]->checkPermission("admin.organizations.delete")) { ?>
                form.createActionButton(<?= json_encode(__("admin.organizations.delete")) ?>, "btn-danger", function() {
                    if(!confirm(<?= json_encode(__("admin.organizations.delete_confirm")) ?>)) return;

                    Z.Request.action("delete", {}, function() {
                        window.location.replace("<?= $opt["root"] . "z/organizations/"; ?>");
                    });
                });
            <?php } ?>
        });
    </script>

    <h2><?= e(__("admin.organizations.title")) ?></h2>

    <div id="form"></div>

    <h3 class="h5 mt-4"><?= e(__("admin.organizations.group")) ?></h3>

    <?php if(is_null($opt["group"])) { ?>
        <div class="alert alert-dark" role="alert">
            <?= e(__("admin.organizations.no_group")) ?>
        </div>
    <?php } else { ?>
        <div class="list-group">
            <span class="list-group-item" data-test="organization-group">
                <code>[<?= e($opt["group"]->id()); ?>]</code>
                <?= e($opt["group"]->name()); ?>
            </span>
        </div>
    <?php } ?>

    <h3 class="h5 mt-4"><?= e(__("admin.organizations.users")) ?></h3>

    <?php if(empty($opt["users"])) { ?>
        <div class="alert alert-dark" role="alert">
            <?= e(__("admin.organizations.no_users")) ?>
        </div>
    <?php } ?>
    <div class="list-group">
        <?php foreach($opt["users"] as $user) { ?>
            <span class="list-group-item d-flex align-items-center" data-test="organization-user-<?= $user->id(); ?>">
                <small class="mr-1">
                    <code>[<?= e($user->id()); ?>]</code>
                </small>

                <?php if(is_null($user->getField("email"))) { ?>
                    <i><?= e(__("admin.organizations.no_email")) ?></i>
                <?php } else { ?>
                    <?= e($user->getField("email")); ?>
                <?php } ?>
            </span>
        <?php } ?>
    </div>
@endsection
