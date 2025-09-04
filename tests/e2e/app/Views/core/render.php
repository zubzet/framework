<?php return [ 'body' => function($opt) { ?>
    <h2 data-test="title">Render</h2>
    <h2 data-test="data"><?= $opt["data"] ?></h2>
<?php }]; ?>