<?php 
/**
 * The default layout
 */

return [
    "lang" => [
        "de_formal" => [
            "administration" => "Verwaltung",
            "instance" => "Instanz",
            "log_statistics" => "Log / Statistik",
            "logout" => "Ausloggen",
            "token_expired_heading" => "Login Abgelaufen",
            "token_expired_explanation" => "Ihr Login ist abgelaufen. Dies bedeutet, dass Sie sich erneut anmelden müssen. Wenn Sie aktuell ungespeicherte Änderungen haben, können Sie diese noch spoeichern. Dafür müssen Sie sich allerdings zunächst erneut anmelden."
        ], 
        "en" => [
            "administration" => "Administration",
            "instance" => "Instance",
            "log_statistics" => "Log / Statistics",
            "logout" => "Logout",
            "token_expired_heading" => "Login Expired",
            "token_expired_explanation" => "Your login has expired. This means that you must log in again. If you currently have unsaved changes, you will be able to save them after logging in again.",
        ]
        ], "layout" => function($opt, $body, $head) {?>
    <!doctype html>
    <html class="no-js" lang="en">
        <head>
            <link rel="icon" type="image/png" href="<?php echo $opt["root"]; ?>assets/img/favicon.png">
            <meta charset="utf-8" />
            <title><?php echo $opt["title"]; ?></title>
            <meta http-equiv="x-ua-compatible" content="ie=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <?php $opt["layout_essentials_head"]($opt); ?>
            <?php $head($opt); ?>
        </head>
        <body>
            <?php $opt["layout_essentials_body"]($opt); ?>
            <?php $body($opt); ?>
        </body>
    </html>
<?php }]; ?>