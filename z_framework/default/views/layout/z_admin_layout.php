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
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css" rel="stylesheet">

        <!-- ESSENTIAL JS START -->
            <script src="<?php echo $opt["root"]; ?>assets/js/Z.js"></script>
        <!-- ESSENTIAL JS END -->
        <?php $head($opt); ?>

        <style>

            .wrapper {
                display: flex;
                margin: 0px;
            }

            .sidebar {
                border-right: 1px solid black;
                padding: 4px;
                min-height: 100vh;
                width: 250px;
                background-color: #202020;
                color: #fff;
            }

            .nav-item {
                font-size: 120%;
                border-bottom: 1px dotted white;
            }

            .nav-item i {
                width: 32px;
                margin: 3px;
            }

            .content {
                margin-left: auto;
                margin-right: auto;
                max-width: 1000px;
            }

            .content-wrapper {
                border-top: 10px #202020 solid;
                flex-grow: 1;
            }

            h1 {
                animation: rainbow 5s infinite;
                animation-timing-function: linear;
                text-shadow: 0 0 1px #fff, 0 0 2px #fff, 0 0 3px #fff, 0 0 4px #00DEFF, 0 0 7px #00DEFF;
            }

            @keyframes rainbow{
                0%{color: orange; transform: scale(1) rotate(10deg); }	
                10%{color: purple;}	
                20%{color: red;}
                25%{transform: scale(1.2) rotate(0deg); }
                30%{color: CadetBlue;}
                40%{color: yellow;}
                50%{color: coral; transform: scale(1) rotate(-10deg);}
                60%{color: green;}
                70%{color: cyan;}
                75%{transform: scale(1.2) rotate(0deg);}
                80%{color: DeepPink;}
                90%{color: DodgerBlue;}
                100%{color: orange; transform: scale(1) rotate(10deg); }
            }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <div class="sidebar">
                <h1 class="text-center">Z-Admin</h1>
                <nav class="side-nav">
                    <a href="<?php echo $opt["root"]; ?>z/cfg_instance"><div class="nav-item"><i class="fa fa-wrench"></i>Instance</div></a>
                    <a href="<?php echo $opt["root"]; ?>z/edit_user"><div class="nav-item"><i class="fa fa-user-edit"></i>Edit User</div></a>
                    <a href="<?php echo $opt["root"]; ?>z/add_user"><div class="nav-item"><i class="fa fa-user-plus"></i></i>Add User</div></a>
                    <a href="<?php echo $opt["root"]; ?>z/log"><div class="nav-item"><i class="fa fa-file"></i>Log</div></a>
                    <a href="<?php echo $opt["root"]; ?>z/roles"><div class="nav-item"><i class="fa fa-user-tag"></i>Roles</div></a>
                    <a href="<?php echo $opt["root"]; ?>login/logout"><div class="nav-item"><i class="fa fa-sign-out-alt"></i>Logout</div></a>
                </nav>
            </div>
            <div class="content-wrapper">
                <div class="content pb-2 pt-2">
                    <?php $body($opt); ?>
                </div>
            </div>
        </div>

        <?php $opt["layout_essentials"]($opt); ?>
    </body>
</html>
<?php } ?>