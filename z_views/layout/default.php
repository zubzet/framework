<?php
    function getLangArrayLayout() {

        return [
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
        ];
    
    }

function layout($opt, $body, $head) {?>
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
        <!-- NAVBAR START -->
        <div class="top-bar foundation-5-top-bar">
            <div class="top-bar-title">
                <span data-responsive-toggle="responsive-menu" data-hide-for="medium">
                <button class="menu-icon" type="button" data-toggle></button>
                </span>
                <a style="color:white;" href="<?php echo $opt["root"]; ?>">
                <span>Your Website</span>
                </a>
            </div>
            <div id="responsive-menu">
                <div class="top-bar-left">
                    <ul class="dropdown vertical medium-horizontal menu" data-responsive-menu="drilldown medium-dropdown" data-auto-height="true" data-animate-height="true">
                        <li>
                            <a href="#"><?php $opt["lang"]("administration"); ?></a>
                            <ul class="menu vertical">
                                <li><a href="<?php echo $opt["root"]; ?>admin/log_statistics"><?php $opt["lang"]("log_statistics"); ?></a></li>
                                <li><a href="<?php echo $opt["root"]; ?>admin/cfg_instance"><?php $opt["lang"]("instance"); ?></a></li>
                            </ul>
                        </li>
                        <li><a href="<?php echo $opt["root"]; ?>login/logout"><?php $opt["lang"]("logout"); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- NAVBAR END -->

        <div class="top-bar-boundry"></div>

        <!-- WRAPPER START -->
        <main style="max-width: 1000px; width: 100%; margin: auto">
            <?php $body($opt); ?>
        </main>
        <!-- WRAPPER END -->

        <?php $opt["layout_essentials"]($opt); ?>
    </body>
</html>
<?php } ?>