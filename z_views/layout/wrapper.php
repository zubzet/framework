<!doctype html>
<html class="no-js" lang="en">
    <head>
        <link rel="icon" type="image/png" href="<?php echo $opt["root"]; ?>assets/img/favicon.png">
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <meta charset="utf-8" />
        <title><?php echo $opt["title"]; ?></title>
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="<?php echo $opt["root"]; ?>assets/css/font-awesome.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/foundation.css">
        <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/main.css">
        <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/form-icons.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">
        <link href="<?php echo $opt["root"]; ?>assets/css/largeHeader.css" rel="stylesheet">
        <?php head($opt); ?>
    </head>
    <body>
        <!-- WRAPPER START -->
        <div class="row align-center medium-10 large-10 small-11 columns container-padded">
            <?php body($opt); ?>
        </div>
        <!-- WRAPPER END -->

        <!-- ESSENTIAL JS START -->
        <script src="<?php echo $opt["root"]; ?>assets/js/foundation.min.js"></script>
        <script> 
        $(() => {
            $(document).foundation(); 
        })
        </script>
        <!-- ESSENTIAL JS END -->
    </body>
</html>