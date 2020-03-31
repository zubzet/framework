<?php return ["layout" => function($opt, $body, $head) { ?>
    <html>
        <head>
            <?php $head($opt); ?>
        </head>
        <body>
            <?php $body($opt); ?>
        </body>
    </html>
<?php }]; ?>