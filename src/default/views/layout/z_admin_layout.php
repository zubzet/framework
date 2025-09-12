<?php return ["layout" => function($opt, $body, $head) {?>
<!doctype html>
<html class="no-js" lang="en">
    <head>
        <link rel="icon" type="image/png" href="<?= $opt["root"]; ?>assets/img/favicon.png">
        <meta charset="utf-8" />
        <title><?= $opt["title"]; ?></title>
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <?php $opt["layout_essentials_head"]($opt); ?>
        <?php $head($opt); ?>

        <style>

            .wrapper {
                display: flex;
                margin: 0px;
            }

            .sidebar {
                border-right: 1px solid black;
                padding: 4px;
                background-color: #202020;
                color: #fff;
            }

            .nav-item i {
                width: 32px;
                margin: 3px;
            }

            .nav-item {
                
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

            @media(min-width: 768px) {
                .sidebar {
                    min-height: 100vh;
                }
            }

            @keyframes rainbow {
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
        <div class="container-fluid">
            <div class="row">
                <div class="sidebar col-md-2 col-12 visible-sm-block hidden-sm">
                    <h1 class="text-center mb-5 mt-4">Z-Admin</h1>
                    <button class="btn btn-primary d-md-none btn-block mb-2" data-toggle="collapse" data-target="#navbar"><i class="fa fa-bars"></i></button>
                    <div id="navbar" class="collapse show">
                        <div class="list-group mb-1">
                            <?php if($opt["user"]->checkPermission("admin.log")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" href="<?= $opt["root"]; ?>z/log"><i class="fa fa-file"></i>Log / Statistics</a>
                            <?php } ?>
                            <?php if($opt["user"]->checkPermission("admin.database")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-database" href="<?= $opt["root"]; ?>z/database">
                                    <i class="fa fa-database"></i>
                                    Database
                                </a>
                            <?php } ?>
                        </div>
                        <div class="list-group mb-1">
                            <?php if($opt["user"]->checkPermission("admin.user.edit")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-edit-user" href="<?= $opt["root"]; ?>z/edit_user"><i class="fa fa-user-edit"></i>Edit User</a>
                            <?php } ?>
                            <?php if($opt["user"]->checkPermission("admin.user.add")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-add-user" href="<?= $opt["root"]; ?>z/add_user"><i class="fa fa-user-plus"></i></i>Add User</a>
                            <?php } ?>
                            <?php if($opt["user"]->checkPermission("admin.roles.list")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-roles" href="<?= $opt["root"]; ?>z/roles"><i class="fa fa-user-tag"></i>Roles</a>
                            <?php } ?>
                        </div>
                        <div class="list-group mb-1">
                            <a class="list-group-item list-group-item-dark list-group-item-action nav-item" href="<?= $opt["root"]; ?>">
                                <i class="fa fa-arrow-left"></i>
                                Go back
                            </a>
                            <a class="list-group-item list-group-item-dark list-group-item-action nav-item" href="<?= $opt["root"]; ?>login/logout"><i class="fa fa-sign-out-alt"></i>Logout</a>
                        </div>
                    </div>
                </div>
                <div class="content<?= ($opt["wideContent"] ?? false) ? "-fluid" : "" ?> pb-2 pt-2 col-md-10">
                    <?php $body($opt); ?>
                </div>
            </div>
        </div>

        <?php $opt["layout_essentials_body"]($opt); ?>
        <script>
            $(function() {
                var isSmall = window.matchMedia("(max-width: 768px)").matches;
                if (isSmall) {
                    $("#navbar").removeClass("show");
                }
            })
        </script>
    </body>
</html>
<?php }]; ?>
