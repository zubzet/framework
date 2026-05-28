<?php
/**
 * The admin dashboard. Cards mirror the sections and items of the sidebar in
 * layout/z_admin_layout.php; cards are only rendered for permissions the
 * requesting user holds.
 */

return ["body" => function($opt) { ?>

    <div class="content">
        <h2 class="mb-3">Dashboard</h2>

        <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
            <i class="fa fa-fw fa-arrow-circle-o-right"></i>
            Application
        </h2>
        <div class="row">
            <?php if($opt["user"]->checkPermission("admin.database")) { ?>
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <a href="<?= "$opt[root]z/database" ?>" data-test="dash-database" class="card shadow-sm h-100 text-decoration-none text-muted">
                        <div class="card-body d-flex align-items-center justify-content-center py-5">
                            <i class="fa fa-database fa-4x"></i>
                        </div>
                    </a>
                </div>
            <?php } ?>
            <?php if($opt["user"]->checkPermission("admin.maintenance")) { ?>
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <a href="<?= "$opt[root]z/maintenance" ?>" data-test="dash-maintenance" class="card shadow-sm h-100 text-decoration-none text-muted">
                        <div class="card-body d-flex align-items-center justify-content-center py-5">
                            <i class="fa fa-wrench fa-4x"></i>
                        </div>
                    </a>
                </div>
            <?php } ?>
        </div>

        <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
            <i class="fa fa-fw fa-arrow-circle-o-right"></i>
            Users and Roles
        </h2>
        <div class="row">
            <?php if($opt["user"]->checkPermission("admin.user.edit")) { ?>
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <a href="<?= "$opt[root]z/edit_user" ?>" data-test="dash-edit-user" class="card shadow-sm h-100 text-decoration-none text-muted">
                        <div class="card-body d-flex align-items-center justify-content-center py-5">
                            <i class="fa fa-user-edit fa-4x"></i>
                        </div>
                    </a>
                </div>
            <?php } ?>
            <?php if($opt["user"]->checkPermission("admin.user.add")) { ?>
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <a href="<?= "$opt[root]z/add_user" ?>" data-test="dash-add-user" class="card shadow-sm h-100 text-decoration-none text-muted">
                        <div class="card-body d-flex align-items-center justify-content-center py-5">
                            <i class="fa fa-user-plus fa-4x"></i>
                        </div>
                    </a>
                </div>
            <?php } ?>
            <?php if($opt["user"]->checkPermission("admin.roles.list")) { ?>
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <a href="<?= "$opt[root]z/roles" ?>" data-test="dash-roles" class="card shadow-sm h-100 text-decoration-none text-muted">
                        <div class="card-body d-flex align-items-center justify-content-center py-5">
                            <i class="fa fa-user-tag fa-4x"></i>
                        </div>
                    </a>
                </div>
            <?php } ?>
            <?php if($opt["user"]->checkPermission("admin.groups.list")) { ?>
                <div class="col-12 col-sm-6 col-md-3 mb-3">
                    <a href="<?= "$opt[root]z/groups" ?>" data-test="dash-groups" class="card shadow-sm h-100 text-decoration-none text-muted">
                        <div class="card-body d-flex align-items-center justify-content-center py-5">
                            <i class="fa fa-user-friends fa-4x"></i>
                        </div>
                    </a>
                </div>
            <?php } ?>
        </div>

        <h2 class="mt-4 mb-2 pl-1 font-weight-bold h5">
            <i class="fa fa-fw fa-arrow-circle-o-right"></i>
            Other
        </h2>
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <a href="<?= "$opt[root]" ?>" data-test="dash-back" class="card shadow-sm h-100 text-decoration-none text-muted">
                    <div class="card-body d-flex align-items-center justify-content-center py-5">
                        <i class="fa fa-arrow-left fa-4x"></i>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <a href="<?= "$opt[root]login/logout" ?>" data-test="dash-logout" class="card shadow-sm h-100 text-decoration-none text-muted">
                    <div class="card-body d-flex align-items-center justify-content-center py-5">
                        <i class="fa fa-sign-out-alt fa-4x"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

<?php }]; ?>
