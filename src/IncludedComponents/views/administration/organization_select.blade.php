@extends($layout)

@section("content")
    <h2>Organizations</h2>

    <?php if(empty($opt["organizations"])) { ?>
        <div class="alert alert-dark" role="alert">
            No organizations found.
        </div>
    <?php } ?>
    <div class="list-group">
        <?php foreach($opt["organizations"] as $organization) { ?>
            <a data-test="organization-<?= $organization["id"]; ?>" href="<?= $opt["root"] . "z/organizations/" . $organization["id"]; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                <small class="mr-1">
                    <code>[<?= e($organization["id"]); ?>]</code>
                </small>

                <?php if(is_null($organization["name"])) { ?>
                    <i>No name</i>
                <?php } else { ?>
                    <?= e($organization["name"]); ?>
                <?php } ?>
            </a>
        <?php } ?>
    </div>

    <?php if($opt["user"]->checkPermission("admin.organizations.create")) { ?>
        <a data-test="organization-create" href="<?= $opt["root"] . "z/add_organization"; ?>" class="btn btn-primary mt-2">Create organization</a>
    <?php } ?>
@endsection
