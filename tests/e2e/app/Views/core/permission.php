<?php return [ 'body' => function($opt) { ?>
    <?php if($opt["user"]->checkPermission("admin")) { ?>
        <h1 data-test="admin">Administrator</h1>
    <?php } ?>
<?php }]; ?>