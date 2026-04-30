<?php  return ["layout" => function($opt, $body, $head) { ?>
    <!doctype html>
    <html class="no-js" lang="en">
        <head>
            <?php $opt["layout_essentials_head"]($opt); ?>
            <?php $head($opt); ?>
        </head>
        <body>
            <h2>New Layout</h2>
            <?php $body($opt); ?>
        </body>
    </html>
<?php }]?>