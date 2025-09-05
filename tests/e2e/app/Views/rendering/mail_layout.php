<?php return ["layout" => function($opt, $body, $head) { ?>
    <html>
        <head>
            <meta charset="utf-8"/>
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <?= $head($opt); ?>
        </head>
        <body>
            <h2>Custom Layout Template</h2>
            <div class="container">
                <div class="card">
                    <?= $body($opt); ?>
                </div>
            <div>
        </body>
    </html>
<?php }]; ?>
