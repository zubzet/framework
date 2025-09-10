<?php return [ "body" => function($opt) { ?>

    <?php $table = $opt["table"]; ?>

    <div class="content">
        <div class="h5 font-weight-bold text-center d-block d-lg-none mt-2 mb-3">
            <?= e($table["name"]) ?>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-3">
            <a href="<?= $opt["root"] ?>z/database">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <div class="font-weight-bold d-none d-lg-block">
                <?= e($table["name"]) ?>
            </div>
            <div class="ml-3">
                <a class="btn btn-sm btn-outline-secondary" href="<?= "$opt[root]z/database/$table[name]/csv" ?>">
                    <i class="fa fa-cloud-download"></i> Export
                </a>
            </div>
        </div>

        <div class="row no-gutters mt-3 mb-4">
            <div class="col-12 col-md-4 pr-md-2 mb-2 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="mb-0">
                            <div class="text-muted small">
                                Amount Of Rows
                            </div>
                            <div class="h4 mb-0">
                                <?= number_format($opt["table"]["totalRows"]) ?>
                            </div>
                        </div>
                        <i class="fa fa-list-ul fa-lg text-muted"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 px-md-2 mb-2 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="mb-0">
                            <div class="text-muted small">
                                Amount Of Columns
                            </div>
                            <div class="h4 mb-0">
                                <?= number_format($table["totalColumns"]) ?>
                            </div>
                        </div>
                        <i class="fa fa-columns fa-lg text-muted"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 pl-md-2">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="mb-0">
                            <div class="text-muted small">
                                Sorted By
                            </div>
                            <div class="h5 mb-0 text-break">
                              <?= e($table["orderBy"]) ?>
                            </div>
                        </div>
                        <i class="fa fa-sort-amount-desc fa-lg text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <nav class="mb-4">
            <ul class="pagination">
                <li class="page-item shadow-sm <?= $opt["page"] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $opt["pageLink"] ?>1">
                        <i class="fa fa-fw fa-step-backward"></i>
                        First
                    </a>
                </li>
    
                <li class="page-item shadow-sm <?= $opt["page"] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= "$opt[pageLink]$opt[paginationLast]" ?>">
                        <i class="fa fa-fw fa-step-backward"></i>
                        Previous
                    </a>
                </li>
    
                <?php for ($p = $opt["paginationStart"]; $p <= $opt["paginationEnd"]; $p++) { ?>
                    <li class="page-item shadow-sm <?= $p == $opt["page"] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= "$opt[pageLink]$p" ?>">
                            <?= e($p) ?>
                        </a>
                    </li>
                <?php } ?>
    
                <li class="page-item shadow-sm <?= $opt["page"] >= $opt["totalPages"] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= "$opt[pageLink]$opt[paginationNext]" ?>">
                        Next
                        <i class="fa fa-fw fa-step-forward"></i>
                    </a>
                </li>
    
                <li class="page-item shadow-sm <?= $opt["page"] >= $opt["totalPages"] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= "$opt[pageLink]$opt[totalPages]" ?>">
                        Last
                        <i class="fa fa-fw fa-fast-forward"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="content<?= $table["totalColumns"] > 5 ? '-fluid' : '' ?>">
        <div class="table-responsive mb-3 shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <?php foreach($table["columns"] as $col) { ?>
                            <th class="text-nowrap">
                                <?= e($col["Field"]) ?>
                                <?php if("PRI" == $col["Key"]) { ?>
                                    <i class="fa fa-key text-warning"></i>
                                <?php } ?>
                            </th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($opt["table"]["rows"] as $r) { ?>
                        <tr class="align-middle">
                            <?php foreach ($table["columns"] as $col) { ?>
                                <td class="text-nowrap">
                                    <?php if(is_null($r[$col["Field"]])) { ?>
                                        <span class="text-muted">—</span>
                                    <?php } else { ?>
                                        <?= e($r[$col["Field"]]) ?>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if (0 == $table["totalRows"]) { ?>
            <div class="alert alert-light border d-flex align-items-center shadow-sm">
                <i class="fa fa-info-circle mr-2"></i>
                <div class="text-muted mb-0">
                    There are no rows in
                </div>
            </div>
        <?php } ?>
    </div>

<?php }]; ?>
