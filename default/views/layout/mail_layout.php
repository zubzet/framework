<?php 
return ["layout" => function($opt, $body, $head) { ?>
    <html lang="en">
        <body>
            <?php $body($opt); ?>
        </body>
    </html>
<?php }]; ?>