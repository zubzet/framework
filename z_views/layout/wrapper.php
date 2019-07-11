<?php function layout($opt, $body, $head) { ?>
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
        <link href="<?php echo $opt["root"]; ?>assets/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">

        <?php $head($opt); ?>
    </head>
    <body>
        <!-- WRAPPER START -->
        <?php $body($opt); ?>
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
<?php } ?>