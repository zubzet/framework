<?php return [ "body" => function($opt) { ?>

    <?php $table = $opt["table"]; ?>

    <div class="d-flex align-items-center justify-content-between mt-3">
                  <a href="<?= $opt["root"] ?>z/database">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        <div>
            <a class="btn btn-sm btn-outline-secondary" href="<?= "$opt[root]z/database/$table[name]/export" ?>">
                <i class="fa fa-cloud-download"></i> Export
            </a>
        </div>
    </div>

    <div class="row no-gutters mt-3 mb-4">
        <div class="col-12 col-md-4 pr-md-2 mb-2 mb-md-0">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">
                            Total Rows
                        </div>
                        <div class="h4 mb-0"><?= number_format($opt["table"]["totalRows"]) ?></div>
                    </div>
                    <i class="fa fa-list-ul fa-lg text-muted"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 px-md-2 mb-2 mb-md-0">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">
                            Columns
                        </div>
                        <div class="h4 mb-0"><?= number_format(count($table["columns"])) ?></div>
                    </div>
                    <i class="fa fa-columns fa-lg text-muted"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 pl-md-2">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">
                            Table
                        </div>
                        <div class="h5 mb-0 text-break">
                          <?= e($table["name"]) ?>
                        </div>
                    </div>
                    <i class="fa fa-database fa-lg text-muted"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($opt["table"]["rows"])) { ?>
        <div class="alert alert-light border d-flex align-items-center">
            <i class="fa fa-info-circle mr-2"></i>
            <div class="text-muted mb-0">No rows found.</div>
        </div>
    <?php } else { ?>
        <div class="table-responsive mb-3">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <?php foreach ($table["columns"] as $col) { ?>
                            <th class="text-nowrap text-break"><?= e($col["Field"]) ?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($opt["table"]["rows"] as $r) { ?>
                        <tr class="align-middle">
                            <?php foreach ($table["columns"] as $col) { ?>
                                <?php
                                $v = $r[$col["Field"]] ?? null;
                                ?>
                                <td class="text-break">
                                    <?php
                                    if ($v === null) {
                                        echo '<span class="text-muted">—</span>';
                                    } else {
                                        echo e($v);
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

<?php }]; ?>
