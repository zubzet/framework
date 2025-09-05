<?php return ["layout" => function ($opt, $body, $head) { ?>
    <!doctype html>
    <html class="no-js" lang="de">
        <head>
            <?php $opt["layout_essentials_head"]($opt); ?>
            <?php $head($opt); ?>
        </head>
        <body id="top" data-test="dashboard-top">
            <div class="container py-5">
                <?php $body($opt); ?>
            </div>

            <?php $opt["layout_essentials_body"]($opt); ?>
        </body>
    </html>
<?php }]; ?>