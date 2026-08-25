@extends($layout)

@section("content")
    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            form.createField({
                name: "name",
                type: "text",
                text: "Name",
                required: true,
                value: <?= json_encode($opt["name"]); ?>,
            });

            form.createActionButton("Delete organization", "btn-danger", function() {
                if(!confirm("Do you really want to delete this organization?")) return;

                Z.Request.action("delete", {}, function() {
                    window.location.replace("<?= $opt["root"] . "z/organizations/"; ?>");
                });
            });
        });
    </script>

    <h2>Organizations</h2>

    <div id="form"></div>

    <h3 class="h5 mt-4">Group</h3>

    <?php if(is_null($opt["group"])) { ?>
        <div class="alert alert-dark" role="alert">
            This organization has no group.
        </div>
    <?php } else { ?>
        <div class="list-group">
            <span class="list-group-item" data-test="organization-group">
                <code>[<?= e($opt["group"]->id()); ?>]</code>
                <?= e($opt["group"]->name()); ?>
            </span>
        </div>
    <?php } ?>

    <h3 class="h5 mt-4">Users</h3>

    <?php if(empty($opt["users"])) { ?>
        <div class="alert alert-dark" role="alert">
            No users belong to this organization.
        </div>
    <?php } ?>
    <div class="list-group">
        <?php foreach($opt["users"] as $user) { ?>
            <span class="list-group-item d-flex align-items-center" data-test="organization-user-<?= $user->id(); ?>">
                <small class="mr-1">
                    <code>[<?= e($user->id()); ?>]</code>
                </small>

                <?php if(is_null($user->getField("email"))) { ?>
                    <i>No email</i>
                <?php } else { ?>
                    <?= e($user->getField("email")); ?>
                <?php } ?>
            </span>
        <?php } ?>
    </div>
@endsection
