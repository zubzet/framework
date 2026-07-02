<!doctype html>
<html class="no-js" lang="en">
    <head>
        <link rel="icon" type="image/png" href="<?php echo $opt["root"]; ?>assets/img/favicon.png">
        <meta charset="utf-8" />
        <title><?php echo $opt["title"]; ?></title>
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php $opt["layout_essentials_head"]($opt); ?>
        @yield("head")
    </head>
    <body>
        <?php $opt["layout_essentials_body"]($opt); ?>
        @yield("body")
    </body>
</html>
