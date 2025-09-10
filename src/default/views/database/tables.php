<?php return [ "body" => function($opt) { ?>

    <div class="row no-gutters mt-3 mb-4">
        <div class="col-12 col-md-6 pr-md-2 mb-2 mb-md-0">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">
                            Amount Of Tables
                        </div>
                        <div class="h4 mb-0"><?= number_format(count($opt["status"]["tables"])) ?></div>
                    </div>
                    <i class="fa fa-table fa-lg text-muted"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 pl-md-2">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">
                            Approx. Amount Of Rows
                        </div>
                        <div class="h4 mb-0"><?= number_format($opt["status"]["approxRows"]) ?></div>
                    </div>
                    <i class="fa fa-database fa-lg text-muted"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Table</th>
                    <th class="text-right text-lg-left">
                        Rows
                        <i class="fa fa-caret-down"></i>
                    </th>
                    <th class="d-none d-lg-table-cell">Created</th>
                    <th class="d-none d-lg-table-cell">Updated</th>
                    <th class="d-none d-lg-table-cell"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($opt["status"]["tables"] as $t) { ?>
                    <?php $rows = (int)($t["Rows"] ?? 0); ?>
                    <tr class="align-middle">
                        <td class="font-weight-bold text-break" title="<?= e($t['Name']) ?>">
                            <a href="<?= "$opt[root]z/database/$t[Name]" ?>" class="<?= 0 === $rows ? 'text-secondary' : '' ?>">
                                <?= e($t["Name"]) ?>
                            </a>

                            <!-- Mobile-only expandable details -->
                            <details class="d-lg-none mt-1 small">
                                <summary class="text-muted">Details</summary>
                                <div class="mt-1">
                                    <div>
                                        <span class="text-muted">Created:</span>
                                        <?= $t["Create_time"] ? date("Y-m-d H:i:s", strtotime($t["Create_time"])) : "—" ?>
                                    </div>
                                    <div>
                                        <span class="text-muted">Updated:</span>
                                        <?= $t["Update_time"] ? date("Y-m-d H:i:s", strtotime($t["Update_time"])) : "—" ?>
                                    </div>
                                    <div class="mt-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= "$opt[root]z/database/$t[Name]" ?>" title="Open table">
                                            <i class="fa fa-arrow-right"></i> Open
                                        </a>
                                    </div>
                                </div>
                            </details>
                        </td>

                        <td class="text-nowrap text-right text-lg-left">
                            <?= e($rows) ?>
                        </td>

                        <td class="text-nowrap d-none d-lg-table-cell">
                            <?= $t["Create_time"] ? date("Y-m-d H:i:s", strtotime($t["Create_time"])) : "—" ?>
                        </td>
                        <td class="text-nowrap d-none d-lg-table-cell">
                            <?= $t["Update_time"] ? date("Y-m-d H:i:s", strtotime($t["Update_time"])) : "—" ?>
                        </td>

                        <td class="text-right">
                            <a class="btn btn-sm btn-outline-primary d-none d-lg-inline-flex" href="<?= "$opt[root]z/database/$t[Name]" ?>" title="Open table">
                                <i class="fa fa-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

<?php }]; ?>
