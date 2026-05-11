<?php return["body" => function($opt) { ?>

    <h2>Groups</h2>

    <?php if(empty($opt["groups"])) { ?>
        <div class="alert alert-dark" role="alert">
            No groups found.
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

<?php }]; ?>