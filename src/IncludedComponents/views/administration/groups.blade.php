@extends($layout)

@section("content")
    <h2><?= e(__("admin.groups.title")) ?></h2>

    <?php if(empty($opt["groups"])) { ?>
        <div class="alert alert-dark" role="alert">
            <?= e(__("admin.groups.empty")) ?>
        </div>
    <?php } ?>
    <div class="list-group">
        <?php foreach($opt["groups"] as $group) { ?>
            <span class="list-group-item" data-test="group-<?= $group["id"]; ?>">
                <code>[<?= $group["id"]; ?>]</code>
                <?= $group["name"]; ?>
            </span>
        <?php } ?>
    </div>
@endsection
