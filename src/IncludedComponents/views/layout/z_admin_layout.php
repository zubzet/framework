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

        <link rel="stylesheet" href="<?= $opt["root"]; ?>_zubzet/asset-proxy/css/admin_layout.css">
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <div class="sidebar col-12 col-lg-3 col-xl-2">
                    <div class="text-center my-4 font-weight-bold">
                        <h1 id="logo">
                            ZubZet
                        </h1>
                        <h1 class="d-none" id="logo-easter-egg">
                            ZubZet
                        </h1>
                    </div>

                    <button class="btn btn-primary btn-block mb-2 d-lg-none" data-toggle="collapse" data-target="#navbar">
                        <i class="fa fa-bars"></i>
                    </button>

                    <div id="navbar" class="collapse show">
                        <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
                            <i class="fa fa-fw fa-arrow-circle-o-right"></i>
                            Application
                        </h1>
                        <div class="list-group mb-1">
                            <?php if($opt["user"]->checkPermission("admin.database")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-database" href="<?= $opt["root"]; ?>z/database">
                                    <i class="fa fa-fw fa-database"></i>
                                    Database
                                </a>
                            <?php } ?>
                            <?php if($opt["user"]->checkPermission("admin.maintenance")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-maintenance" href="<?= $opt["root"]; ?>z/maintenance">
                                    <i class="fa fa-fw fa-wrench"></i>
                                    Maintenance
                                </a>
                            <?php } ?>
                        </div>

                        <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
                            <i class="fa fa-fw fa-arrow-circle-o-right"></i>
                            Users and Roles
                        </h1>
                        <div class="list-group mb-1">
                            <?php if($opt["user"]->checkPermission("admin.user.edit")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-edit-user" href="<?= $opt["root"]; ?>z/edit_user">
                                    <i class="fa fa-fw fa-user-edit"></i>
                                    Edit User
                                </a>
                            <?php } ?>
                            <?php if($opt["user"]->checkPermission("admin.user.add")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-add-user" href="<?= $opt["root"]; ?>z/add_user">
                                    <i class="fa fa-fw fa-user-plus"></i>
                                    Add User
                                </a>
                            <?php } ?>
                            <?php if($opt["user"]->checkPermission("admin.roles.list")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-roles" href="<?= $opt["root"]; ?>z/roles">
                                    <i class="fa fa-fw fa-user-tag"></i>
                                    Roles
                                </a>
                            <?php } ?>
                            <?php if($opt["user"]->checkPermission("admin.groups.list")) { ?>
                                <a class="list-group-item list-group-item-dark list-group-item-action nav-item" data-test="btn-groups" href="<?= $opt["root"]; ?>z/groups">
                                    <i class="fa fa-fw fa-user-friends"></i>
                                    Groups
                                </a>
                            <?php } ?>
                        </div>

                        <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
                            <i class="fa fa-fw fa-arrow-circle-o-right"></i>
                            Other
                        </h1>
                        <div class="list-group mb-1">
                            <a class="list-group-item list-group-item-dark list-group-item-action nav-item" href="<?= $opt["root"]; ?>">
                                <i class="fa fa-fw fa-arrow-left"></i>
                                Go back
                            </a>
                            <a class="list-group-item list-group-item-dark list-group-item-action nav-item" href="<?= $opt["root"]; ?>login/logout">
                                <i class="fa fa-fw fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
                <div class="content<?= ($opt["wideContent"] ?? false) ? "-fluid" : "" ?> pb-2 pt-2 col-lg-9 col-xl-10">
                    <?php $body($opt); ?>
                </div>
            </div>
        </div>

        <?php $opt["layout_essentials_body"]($opt); ?>

        <script>
            $(() => {
                const mql = window.matchMedia("(max-width: 992px)");
                if(mql.matches) $("#navbar").removeClass("show");
                mql.addEventListener("change", (e) => {
                    if(!e.matches) $("#navbar").addClass("show");
                });
            })

            $(document).on("keydown", function(e) {
                if(!e.ctrlKey || e.key != "j") return;
                e.preventDefault();

                $("#logo-easter-egg").toggleClass("d-none");
                $("#logo").toggleClass("d-none");

                localStorage.setItem(
                    "z_admin_logo_visible",
                    $("#logo").hasClass("d-none"),
                );
            });

            $(() => {
                if("true" == localStorage.getItem("z_admin_logo_visible")) {
                    $("#logo-easter-egg").removeClass("d-none");
                    $("#logo").addClass("d-none");
                }
            });
        </script>
    </body>
</html>
<?php }]; ?>
