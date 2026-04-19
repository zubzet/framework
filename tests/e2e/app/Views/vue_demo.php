<?php return ["body" => function($opt) {
    $opt["assets"]->module("vue-counter");
?>
    <h1 data-test="vue-demo-heading">Vue Demo</h1>
    <div id="vue-counter-app" data-test="vue-counter-root"></div>
<?php }]; ?>
